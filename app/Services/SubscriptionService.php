<?php

namespace App\Services;

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

/**
 * Assinatura do treinador COM a plataforma SaaS.
 * Não misturar com PaymentService (cobrança do aluno ao treinador).
 *
 * Caminho único de escrita: manual (admin), gratuito automático ou pagamento
 * online (checkout transparente: cartão ou Pix) com confirmação por webhook.
 */
class SubscriptionService
{
    public function __construct(private PagBankService $gateway) {}

    /**
     * Atribui o plano gratuito ao treinador (idempotente).
     * Chamado no cadastro de professor; não rebaixa quem já tem plano.
     */
    public function assignFreePlan(Teacher $teacher): ?Subscription
    {
        // Consulta direta (sem memoizar relation): chamadas repetidas no mesmo
        // request/instância não podem gerar duplicata.
        if ($existing = $teacher->subscription()->first()) {
            return $existing;
        }

        $freePlan = $this->freePlan();

        if (! $freePlan) {
            return null;
        }

        return DB::transaction(function () use ($teacher, $freePlan) {
            $subscription = new Subscription;
            $subscription->teacher_id = $teacher->id;
            $subscription->plan_id = $freePlan->id;
            $subscription->period = BillingPeriod::MONTHLY;
            $subscription->gateway = Subscription::GATEWAY_MANUAL;
            $subscription->status = SubscriptionStatus::ACTIVE;
            $subscription->amount = 0;
            $subscription->starts_at = now()->toDateString();
            $subscription->ends_at = null;
            $subscription->save();

            return $subscription;
        });
    }

    /**
     * Ativa (ou troca para) um plano gratuito na hora, sem gateway.
     * Plano pago usa payWithCard/payWithPix.
     */
    public function activateFreePlan(Teacher $teacher, SaasPlan $plan, BillingPeriod $period = BillingPeriod::MONTHLY): Subscription
    {
        if (! $plan->isFree()) {
            throw new \RuntimeException('Este plano exige pagamento. Tente novamente pelo checkout.');
        }

        $subscription = $teacher->subscription ?? new Subscription;

        $subscription->teacher_id = $teacher->id;
        $subscription->plan_id = $plan->id;
        $subscription->period = $period;
        $subscription->amount = $plan->priceFor($period);
        $subscription->gateway = Subscription::GATEWAY_MANUAL;
        $subscription->status = SubscriptionStatus::ACTIVE;
        $subscription->payment_id = null;
        $subscription->cancelled_at = null;
        $subscription->starts_at = now()->toDateString();
        $subscription->ends_at = null;
        $subscription->save();

        return $subscription;
    }

    /**
     * Pagamento da assinatura com cartão (checkout transparente: o cartão é
     * digitado no próprio sistema e criptografado no navegador).
     *
     * O plano/período são aplicados ANTES da chamada ao gateway e ficam PENDING:
     * assim, se o pagamento ficar "aguardando" (análise de risco), o webhook
     * finaliza com os dados certos. Em recusa/erro, o snapshot do estado
     * anterior é restaurado (quem tinha plano vigente não perde nada).
     *
     * @param  array{encrypted: string, holder: string, installments: int|string, cpf: string}  $card
     * @return array{status: string, message?: string}
     *
     * @throws \Throwable do gateway (assinatura restaurada antes de re-lançar).
     */
    public function payWithCard(Teacher $teacher, SaasPlan $plan, BillingPeriod $period, array $card): array
    {
        $this->ensureGateway();

        [$subscription, $snapshot] = $this->preparePending($teacher, $plan, $period);

        try {
            $order = $this->gateway->createCardOrder($subscription, $card, $this->payer($teacher));
        } catch (\Throwable $e) {
            $this->restoreSnapshot($subscription, $snapshot);

            throw $e;
        }

        switch ($order['gateway_status']) {
            case 'PAID':
                $this->activateSubscription($subscription, (string) $order['id']);

                return ['status' => 'approved'];

            case 'WAITING':
            case 'IN_ANALYSIS':
                // Aguardando o banco: o webhook (ou "já efetuei o pagamento") conclui.
                $subscription->payment_id = (string) $order['id'];
                $subscription->save();

                return ['status' => 'pending'];

            default:
                // Recusa (e estados inesperados): volta ao que era antes.
                $this->restoreSnapshot($subscription, $snapshot);

                return [
                    'status' => 'rejected',
                    'message' => $this->gateway->rejectionMessage($order['code'] ?? '', $order['message'] ?? ''),
                ];
        }
    }

    /**
     * Pagamento da assinatura com Pix: cria o pedido no gateway e devolve o
     * QR Code (imagem + copia e cola) para exibir no próprio modal.
     * A assinatura fica PENDING até a confirmação do pagamento.
     *
     * @return array{status: string, order_id: string, qr_code: string, qr_image: string}
     *
     * @throws \Throwable do gateway (assinatura restaurada antes de re-lançar).
     */
    public function payWithPix(Teacher $teacher, SaasPlan $plan, BillingPeriod $period): array
    {
        $this->ensureGateway();

        [$subscription, $snapshot] = $this->preparePending($teacher, $plan, $period);

        try {
            $order = $this->gateway->createPixOrder($subscription, $this->payer($teacher));
        } catch (\Throwable $e) {
            $this->restoreSnapshot($subscription, $snapshot);

            throw $e;
        }

        if ($order['qr_code_text'] === '') {
            $this->restoreSnapshot($subscription, $snapshot);

            throw new \RuntimeException('Não foi possível gerar o QR Code Pix. Tente novamente ou escolha outra forma de pagamento.');
        }

        $subscription->payment_id = (string) $order['id'];
        $subscription->save();

        return [
            'status' => 'pending',
            'order_id' => (string) $order['id'],
            'qr_code' => (string) $order['qr_code_text'],
            'qr_image' => (string) $order['qr_code_image'],
        ];
    }

    /**
     * Reconsulta o pedido no gateway e aplica o estado à assinatura.
     * Usado pelo webhook e pelo botão "Já efetuei o pagamento".
     *
     * @param  string  $orderId  id do pedido no gateway (ORDE_...)
     * @param  string|null  $reference  referência `sub-{id}` (quando a notificação não traz o pedido)
     * @return Subscription|null assinatura alterada (null = nada a fazer)
     */
    public function syncFromOrder(string $orderId = '', ?string $reference = null): ?Subscription
    {
        if (! str_starts_with(strtoupper($orderId), 'ORDE')) {
            // Notificação sem o id do pedido: usa o vínculo guardado na assinatura.
            $orderId = $reference
                ? (string) ($this->findByExternalReference($reference)?->payment_id ?? '')
                : '';
        }

        if ($orderId === '') {
            return null;
        }

        $order = $this->gateway->fetchOrder($orderId);

        return $order ? $this->applyOrder($order) : null;
    }

    /**
     * Aplica o estado do pedido à assinatura correspondente (referência
     * `sub-{id}`). O corpo da notificação NÃO é fonte de verdade: sempre
     * partimos do pedido reconsultado no gateway.
     *
     * @param  array<string, mixed>  $order  pedido normalizado (PagBankService)
     */
    public function applyOrder(array $order): ?Subscription
    {
        $subscription = $this->findByExternalReference($order['reference_id'] ?? null);

        if (! $subscription) {
            return null;
        }

        $orderId = (string) ($order['id'] ?? '');
        $gatewayStatus = (string) ($order['gateway_status'] ?? '');

        // Pagamento confirmado → ativa/renova com a vigência do período.
        if ($gatewayStatus === 'PAID') {
            $this->activateSubscription($subscription, $orderId);

            return $subscription;
        }

        // Estorno/cancelamento/contestamento de um pedido pago → em atraso (admin age).
        if (in_array($gatewayStatus, ['REFUNDED', 'IN_DISPUTE', 'CANCELED'], true)
            && $subscription->status === SubscriptionStatus::ACTIVE
            && $subscription->payment_id === $orderId
        ) {
            $subscription->status = SubscriptionStatus::PAST_DUE;
            $subscription->save();

            return $subscription;
        }

        // Cartão recusado: interessa só quem está esperando pagamento.
        if ($gatewayStatus === 'DECLINED' && $subscription->status === SubscriptionStatus::PENDING) {
            $subscription->status = SubscriptionStatus::PAST_DUE;
            $subscription->save();

            return $subscription;
        }

        // WAITING/IN_ANALYSIS: aguarda o próximo evento.
        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(Teacher $teacher, array $data): Subscription
    {
        $subscription = new Subscription;
        $subscription->teacher_id = $teacher->id;
        $subscription->fill($data);
        $this->applyCancelledAt($subscription);
        $subscription->save();

        return $subscription;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Subscription $subscription, array $data): Subscription
    {
        $subscription->fill($data);
        $this->applyCancelledAt($subscription);
        $subscription->save();

        return $subscription;
    }

    private function ensureGateway(): void
    {
        if (! $this->gateway->isConfigured()) {
            throw new \RuntimeException('Pagamento online indisponível no momento. Fale com o suporte.');
        }
    }

    /**
     * Assinatura no plano/período escolhido, aguardando pagamento, devolvendo
     * junto o snapshot do estado anterior (rollback se o gateway falhar).
     *
     * @return array{0: Subscription, 1: array<string, mixed>|null}
     */
    private function preparePending(Teacher $teacher, SaasPlan $plan, BillingPeriod $period): array
    {
        $subscription = $teacher->subscription ?? new Subscription;
        $snapshot = $subscription->exists ? $subscription->getAttributes() : null;

        $subscription->teacher_id = $teacher->id;
        $subscription->plan_id = $plan->id;
        $subscription->period = $period;
        $subscription->amount = $plan->priceFor($period);
        $subscription->gateway = Subscription::GATEWAY_PAGBANK;
        $subscription->status = SubscriptionStatus::PENDING;
        $subscription->payment_id = null;
        $subscription->cancelled_at = null;
        $subscription->starts_at = now()->toDateString();
        $subscription->ends_at = null;
        $subscription->save();

        return [$subscription, $snapshot];
    }

    /** Vigência = data do pagamento + meses do período; renovação reinicia. */
    private function activateSubscription(Subscription $subscription, string $orderId): void
    {
        $subscription->status = SubscriptionStatus::ACTIVE;
        $subscription->payment_id = $orderId !== '' ? $orderId : $subscription->payment_id;
        $subscription->cancelled_at = null;
        $subscription->starts_at = now()->toDateString();

        if ($subscription->period) {
            $subscription->ends_at = now()->addMonthsNoOverflow($subscription->period->months())->toDateString();
        }

        $subscription->save();
    }

    /**
     * Dados do pagador enviados ao gateway (nome/e-mail do dono da conta e o
     * CPF quando cadastrado — nunca pedidos de novo no checkout).
     *
     * @return array{name?: string, email?: string, tax_id?: string}
     */
    private function payer(Teacher $teacher): array
    {
        $user = $teacher->user;

        $taxId = $user?->cpf ? preg_replace('/\D/', '', (string) $user->cpf) : '';

        return array_filter([
            'name' => $user?->name ?? $teacher->name,
            'email' => $user?->email,
            'tax_id' => strlen((string) $taxId) === 11 ? $taxId : null,
        ], fn ($value) => filled($value));
    }

    private function findByExternalReference(?string $externalReference): ?Subscription
    {
        if (! $externalReference || ! str_starts_with($externalReference, 'sub-')) {
            return null;
        }

        return Subscription::find((int) substr($externalReference, 4));
    }

    /**
     * Restaura o estado anterior da assinatura (recusa/erro do gateway).
     * Linha criada só para este pagamento e não confirmada → é removida.
     *
     * @param  array<string, mixed>|null  $snapshot
     */
    private function restoreSnapshot(Subscription $subscription, ?array $snapshot): void
    {
        if ($snapshot === null) {
            $subscription->delete();

            return;
        }

        foreach ($snapshot as $column => $value) {
            $subscription->{$column} = $value;
        }

        $subscription->save();
    }

    /** Catálogo: primeiro plano gratuito ativo (slug "gratuito" ou preço zero). */
    private function freePlan(): ?SaasPlan
    {
        return SaasPlan::query()
            ->where('active', true)
            ->where('slug', 'gratuito')
            ->first()
            ?? SaasPlan::query()
                ->where('active', true)
                ->where('price_monthly', 0)
                ->orderBy('sort_order')
                ->first();
    }

    /** Rastro manual de billing: cancelada → registra o momento. */
    private function applyCancelledAt(Subscription $subscription): void
    {
        if ($subscription->status === SubscriptionStatus::CANCELLED && ! $subscription->cancelled_at) {
            $subscription->cancelled_at = now();
        }
    }
}
