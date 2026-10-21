<?php

namespace App\Services;

use App\Enums\PaymentChannel;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Models\Config;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Cobrança do aluno (manual e online).
 *
 * Caminho único de escrita: o treinador cria a cobrança no painel/API, o
 * aluno paga a online pelo app (Pix) e o gateway confirma via webhook.
 * O treinador recebe `net_amount` (valor - taxa da plataforma) no repasse.
 */
class PaymentService
{
    /** Limite de cobranças manuais por mês, por treinador. */
    public const MANUAL_MONTHLY_LIMIT = 10;

    public function __construct(private PagBankService $gateway) {}

    /**
     * Cria a cobrança. `channel=online` exige conta no gateway; `manual`
     * (padrão) respeita o limite mensal.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(Student $student, array $data): Payment
    {
        $channel = $this->channelFrom($data);

        if ($channel === PaymentChannel::MANUAL) {
            $this->ensureManualQuota((int) $student->teacher_id);
        }

        $payment = new Payment;
        $payment->teacher_id = $student->teacher_id;
        $payment->student_id = $student->id;
        $payment->channel = $channel;
        // Campos do gateway não são fillable — o request não os define.
        $payment->fill($data);

        // O default do banco não volta para o objeto recém-criado; sem isto a
        // resposta da API sairia com `status: null` (e `payable: false`).
        if ($payment->status === null) {
            $payment->status = PaymentStatus::PENDING;
        }

        $this->applyPaidAt($payment, $data);
        $this->assertOnlinePaymentIsNotManuallyPaid($payment);
        $this->syncPlatformFee($payment);
        $payment->save();

        return $payment;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Payment $payment, array $data): Payment
    {
        // Cobrança já vinculada a um repasse é histórico financeiro: não muda.
        if ($payment->payout_id !== null) {
            throw ValidationException::withMessages([
                'payment' => 'Esta cobrança já faz parte de um repasse e não pode mais ser alterada.',
            ]);
        }

        $originalAmount = (float) $payment->amount;
        $originalChannel = $payment->channel;
        $originalStatus = $payment->status;
        $originalPaidAt = $payment->paid_at;

        // Cobrança paga (manual também) não troca de canal: evita transformar
        // um recebimento manual em uma cobrança online (e vice-versa).
        if ($originalStatus === PaymentStatus::PAID
            && array_key_exists('channel', $data)
            && $data['channel'] !== $originalChannel?->value
        ) {
            throw ValidationException::withMessages([
                'channel' => 'O canal de uma cobrança paga não pode ser alterado.',
            ]);
        }

        $payment->fill($data);
        $this->applyPaidAt($payment, $data);

        if (! ($originalChannel === PaymentChannel::ONLINE && $originalStatus === PaymentStatus::PAID)) {
            $this->assertOnlinePaymentIsNotManuallyPaid($payment);
        }

        // Cobrança online paga só é alterada pelo gateway (webhook). O painel
        // pode corrigir textos, mas nunca valor/canal/status de um pagamento
        // confirmado — senão a taxa, o líquido e o repasse ficariam irreais.
        if ($originalChannel === PaymentChannel::ONLINE && $originalStatus === PaymentStatus::PAID) {
            $payment->amount = $originalAmount;
            $payment->channel = $originalChannel;
            $payment->status = $originalStatus;
            $payment->paid_at = $originalPaidAt;

            if (array_key_exists('amount', $data) && abs((float) $data['amount'] - $originalAmount) > 0.001) {
                throw ValidationException::withMessages([
                    'amount' => 'O valor de uma cobrança online paga não pode ser alterado.',
                ]);
            }

            if (array_key_exists('channel', $data) && $data['channel'] !== $originalChannel?->value) {
                throw ValidationException::withMessages([
                    'channel' => 'O canal de uma cobrança online paga não pode ser alterado.',
                ]);
            }

            if (array_key_exists('status', $data) && $data['status'] !== $originalStatus?->value) {
                throw ValidationException::withMessages([
                    'status' => 'Cobranças online são confirmadas automaticamente pelo gateway.',
                ]);
            }
        }

        if ($originalStatus === PaymentStatus::PAID
            && array_key_exists('amount', $data)
            && abs((float) $data['amount'] - $originalAmount) > 0.001
        ) {
            throw ValidationException::withMessages([
                'amount' => 'O valor de uma cobrança paga não pode ser alterado.',
            ]);
        }

        $this->syncPlatformFee($payment);
        $payment->save();

        return $payment;
    }

    public function delete(Payment $payment): void
    {
        if ($payment->status === PaymentStatus::PAID || $payment->payout_id !== null) {
            throw ValidationException::withMessages([
                'payment' => 'Cobranças pagas ou já incluídas em repasse não podem ser excluídas. Cancele o repasse antes, se necessário.',
            ]);
        }

        $payment->delete();
    }

    /**
     * Gera o Pix da cobrança online (QR Code + copia e cola) na conta do
     * gateway da plataforma. A cobrança fica pendente até a confirmação.
     *
     * @param  array{cpf?: string|null}  $data  CPF do pagador (fallback: cadastro do aluno)
     * @return array{status: string, order_id: string, qr_code: string, qr_image: string}
     *
     * @throws ValidationException|\RuntimeException
     */
    public function checkoutPix(Payment $payment, array $data = []): array
    {
        $this->ensureGateway();

        if (! $payment->isOnline()) {
            throw ValidationException::withMessages([
                'payment' => 'Esta cobrança é manual e não pode ser paga pelo app.',
            ]);
        }

        if ($payment->status === PaymentStatus::PAID) {
            throw ValidationException::withMessages([
                'payment' => 'Esta cobrança já está paga.',
            ]);
        }

        if ($payment->status === PaymentStatus::CANCELLED) {
            throw ValidationException::withMessages([
                'payment' => 'Esta cobrança está cancelada.',
            ]);
        }

        $payer = $this->payer($payment, $data['cpf'] ?? null);

        // O gateway exige a identificação do pagador (`customer.tax_id`) no Pix.
        if (blank($payer['tax_id'] ?? null)) {
            throw ValidationException::withMessages([
                'cpf' => 'Informe seu CPF para gerar o QR Code do Pix.',
            ]);
        }

        $order = $this->gateway->createPixOrder($this->charge($payment), $payer);

        if ($order['qr_code_text'] === '') {
            throw new \RuntimeException('Não foi possível gerar o QR Code Pix. Tente novamente.');
        }

        $payment->payment_method = PaymentMethod::PIX;
        $payment->gateway = Payment::GATEWAY_PAGBANK;
        $payment->gateway_order_id = (string) $order['id'];
        $payment->gateway_status = (string) $order['gateway_status'];
        $payment->save();

        return [
            'status' => 'pending',
            'order_id' => (string) $order['id'],
            'qr_code' => (string) $order['qr_code_text'],
            'qr_image' => (string) $order['qr_code_image'],
        ];
    }

    /**
     * Reconsulta o pedido no gateway e aplica o estado à cobrança.
     * Usado pelo webhook (referência `pay-{id}`).
     */
    public function syncFromOrder(string $orderId = '', ?string $reference = null): ?Payment
    {
        if (! str_starts_with(strtoupper($orderId), 'ORDE')) {
            $orderId = $reference
                ? (string) ($this->findByExternalReference($reference)?->gateway_order_id ?? '')
                : '';
        }

        if ($orderId === '') {
            return null;
        }

        $order = $this->gateway->fetchOrder($orderId);

        return $order ? $this->applyOrder($order) : null;
    }

    /**
     * Aplica o estado do pedido (corpo da notificação NÃO é fonte de verdade)
     * à cobrança correspondente à referência `pay-{id}`.
     *
     * @param  array<string, mixed>  $order
     */
    public function applyOrder(array $order): ?Payment
    {
        $payment = $this->findByExternalReference($order['reference_id'] ?? null);

        if (! $payment) {
            return null;
        }

        // Só cobranças online passam pelo gateway — uma referência forjada para
        // uma cobrança manual não pode marcar nada como pago.
        if (! $payment->isOnline()) {
            return null;
        }

        $gatewayStatus = (string) ($order['gateway_status'] ?? '');

        if ($gatewayStatus !== '') {
            $payment->gateway_status = $gatewayStatus;
        }

        // Pagamento confirmado.
        if (($order['status'] ?? '') === 'approved') {
            // Webhook repetido: preserva taxa/líquido já calculados (a taxa da
            // plataforma pode ter mudado depois do pagamento).
            if ($payment->status === PaymentStatus::PAID) {
                $payment->gateway_status = 'PAID';
                $payment->save();

                return $payment;
            }

            $expectedCents = (int) round(((float) $payment->amount) * 100);
            $orderCents = (int) ($order['amount_cents'] ?? 0);

            if ($orderCents > 0 && $orderCents !== $expectedCents) {
                Log::error('Gateway: valor do pedido divergente da cobrança', [
                    'payment_id' => $payment->id,
                    'order_id' => (string) ($order['id'] ?? ''),
                    'expected_cents' => $expectedCents,
                    'received_cents' => $orderCents,
                ]);

                $payment->save();

                return $payment;
            }

            $this->markPaid($payment, (string) ($order['id'] ?? ''));

            return $payment;
        }

        // Estorno/contestamento de um pagamento já confirmado → reverte.
        if (in_array($gatewayStatus, ['REFUNDED', 'IN_DISPUTE', 'CANCELED'], true)
            && $payment->status === PaymentStatus::PAID
        ) {
            $this->reverse($payment);

            return $payment;
        }

        // Cancelamento de um Pix ainda pendente (só do pedido atual).
        if ($gatewayStatus === 'CANCELED'
            && $payment->status !== PaymentStatus::PAID
            && in_array($payment->status, [PaymentStatus::PENDING, PaymentStatus::OVERDUE], true)
            && ($payment->gateway_order_id === '' || $payment->gateway_order_id === (string) ($order['id'] ?? ''))
        ) {
            $payment->status = PaymentStatus::CANCELLED;
            $payment->save();

            return $payment;
        }

        // WAITING/IN_ANALYSIS/rejeição: apenas registra o status do gateway.
        $payment->save();

        return $payment;
    }

    private function channelFrom(array $data): PaymentChannel
    {
        return PaymentChannel::tryFrom((string) ($data['channel'] ?? '')) ?? PaymentChannel::MANUAL;
    }

    /**
     * Quantas cobranças **manuais** o treinador já lançou no mês corrente.
     * Usado no bloqueio da criação (limite mensal) e nos indicadores do painel.
     */
    public function manualCountThisMonth(int $teacherId): int
    {
        return Payment::withoutGlobalScope('tenant')
            ->where('teacher_id', $teacherId)
            ->where('channel', PaymentChannel::MANUAL)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    /** Bloqueia a criação da 11ª cobrança manual no mês. */
    private function ensureManualQuota(int $teacherId): void
    {
        if ($this->manualCountThisMonth($teacherId) < self::MANUAL_MONTHLY_LIMIT) {
            return;
        }

        throw ValidationException::withMessages([
            'channel' => sprintf(
                'Limite de %d cobranças manuais por mês atingido. Use a cobrança online para as próximas.',
                self::MANUAL_MONTHLY_LIMIT,
            ),
        ]);
    }

    /** Marca paga, aplica a taxa da plataforma e o líquido do treinador. */
    private function markPaid(Payment $payment, string $orderId): void
    {
        $payment->status = PaymentStatus::PAID;
        $payment->paid_at = $payment->paid_at ?? now();
        $payment->gateway = Payment::GATEWAY_PAGBANK;
        $payment->gateway_status = 'PAID';

        if ($orderId !== '') {
            $payment->gateway_order_id = $orderId;
        }

        $this->applyPlatformFee($payment);

        $payment->save();
    }

    /**
     * Mantém taxa/líquido coerentes com o status: cobrança online paga tem
     * taxa da plataforma; nos demais casos os campos ficam nulos.
     */
    private function syncPlatformFee(Payment $payment): void
    {
        if (! $payment->isOnline()) {
            $payment->fee_amount = null;
            $payment->net_amount = null;

            return;
        }

        if ($payment->status === PaymentStatus::PAID) {
            $this->applyPlatformFee($payment);

            return;
        }

        $payment->fee_amount = null;
        $payment->net_amount = null;
    }

    /** Calcula a taxa da plataforma (`config.fee_percent`) sobre o valor. */
    private function applyPlatformFee(Payment $payment): void
    {
        $percent = Config::feePercent();
        $amount = (float) $payment->amount;
        $fee = round($amount * $percent / 100, 2);

        $payment->fee_amount = $fee;
        $payment->net_amount = round($amount - $fee, 2);
    }

    /** Reverte uma cobrança online paga (estorno/contestamento). */
    private function reverse(Payment $payment): void
    {
        $payment->status = PaymentStatus::CANCELLED;
        $payment->paid_at = null;
        $payment->fee_amount = null;
        $payment->net_amount = null;

        $payout = $payment->payout;

        // Só solta do repasse se ele ainda não foi pago (saldo volta a existir).
        if ($payout?->isPaid() !== true) {
            $payment->payout_id = null;
        }

        $payment->save();

        // Estorno de cobrança que estava em um saque ainda pendente: recalcula
        // o saque (ou cancela se não sobrou nenhuma cobrança nele).
        if ($payout !== null && $payout->status === PayoutStatus::REQUESTED && $payment->payout_id === null) {
            $remaining = Payment::withoutGlobalScope('tenant')
                ->where('payout_id', $payout->id)
                ->get();

            if ($remaining->isEmpty()) {
                $payout->status = PayoutStatus::CANCELLED;
                $payout->paid_at = null;
                $payout->save();
            } else {
                $payout->amount = round((float) $remaining->sum('amount'), 2);
                $payout->fee_total = round((float) $remaining->sum('fee_amount'), 2);
                $payout->net_amount = round((float) $remaining->sum('net_amount'), 2);
                $payout->save();
            }
        }
    }

    /** Cobrança online só nasce/fica paga pela confirmação do gateway. */
    private function assertOnlinePaymentIsNotManuallyPaid(Payment $payment): void
    {
        if ($payment->isOnline() && $payment->status === PaymentStatus::PAID) {
            throw ValidationException::withMessages([
                'status' => 'Cobranças online são confirmadas automaticamente pelo gateway. Use o canal manual para registrar um pagamento já recebido.',
            ]);
        }
    }

    private function ensureGateway(): void
    {
        if (! $this->gateway->isConfigured()) {
            throw new \RuntimeException('Pagamento online indisponível no momento. Tente novamente mais tarde.');
        }
    }

    /**
     * "Subject" da cobrança enviado ao gateway (referência `pay-{id}`).
     *
     * @return array{reference: string, description: string, amount_cents: int}
     */
    private function charge(Payment $payment): array
    {
        return [
            'reference' => $payment->externalReference(),
            'description' => $payment->description,
            'amount_cents' => (int) round(((float) $payment->amount) * 100),
        ];
    }

    /**
     * Dados do pagador (aluno) enviados ao gateway. O CPF do request tem
     * prioridade; senão usa o documento do cadastro do aluno.
     *
     * @return array{name?: string, email?: string, tax_id?: string}
     */
    private function payer(Payment $payment, ?string $taxId = null): array
    {
        $student = $payment->student;

        $digits = preg_replace('/\D/', '', (string) ($taxId ?: $student?->document));

        return array_filter([
            'name' => $student?->name,
            'email' => $student?->email,
            'tax_id' => strlen($digits) === 11 ? $digits : null,
        ], fn ($value) => filled($value));
    }

    private function findByExternalReference(?string $externalReference): ?Payment
    {
        if (! $externalReference || ! str_starts_with($externalReference, 'pay-')) {
            return null;
        }

        return Payment::withoutGlobalScope('tenant')
            ->where('channel', PaymentChannel::ONLINE)
            ->find((int) substr($externalReference, 4));
    }

    private function applyPaidAt(Payment $payment, array $data): void
    {
        $status = PaymentStatus::tryFrom($data['status'] ?? '') ?? $payment->status;

        if ($status === PaymentStatus::PAID) {
            $payment->paid_at = $payment->paid_at ?? now();

            return;
        }

        if (array_key_exists('status', $data)) {
            $payment->paid_at = null;
        }
    }
}
