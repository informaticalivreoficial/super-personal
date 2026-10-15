<?php

namespace App\Livewire\Dashboard;

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Http\Requests\PayWithCardRequest;
use App\Models\SaasPlan;
use App\Services\PagBankService;
use App\Services\SubscriptionService;
use App\Traits\ValidatesWithFormRequest;
use App\Traits\WithToastr;
use Livewire\Attributes\Title;
use Livewire\Component;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Página "Minha assinatura" do treinador: consulta a própria assinatura
 * e assina/alterna planos do catálogo.
 *
 * Gratuito → ativa na hora. Pago → checkout transparente num modal desta
 * página: cartão (criptografado no navegador) ou Pix (QR Code exibido aqui).
 * O usuário NUNCA é redirecionado para fora do sistema.
 * O admin continua podendo ajustar manualmente no card ProfessorSubscription.
 */
class MySubscription extends Component
{
    use ValidatesWithFormRequest, WithToastr;

    /** Modal do checkout transparente. */
    public bool $showPaymentForm = false;

    /** Plano/período escolhidos para o pagamento em andamento (server-side). */
    public string $payPlanId = '';

    public string $payPeriod = 'monthly';

    public float $payAmount = 0;

    public string $payPlanName = '';

    /** Meio de pagamento da aba aberta: card|pix. */
    public string $payMethod = 'card';

    /** Chave pública usada pelo navegador para criptografar o cartão. */
    public string $pagbankPublicKey = '';

    /** CPF do titular (máscara no form; higienizado antes da validação). */
    public string $cpf = '';

    /** Pix gerado: copia e cola (o QR é desenhado localmente a partir dele). */
    public bool $pixGenerated = false;

    public string $pixCode = '';

    public function mount(): void
    {
        // Só o treinador chega aqui (middleware role:teacher); garante perfil.
        abort_unless(auth()->user()?->teacher, 403, 'Perfil de treinador não encontrado.');
    }

    /**
     * Assina (ou troca para) o plano no período indicado.
     * Gratuito → ativa na hora; pago → abre o checkout transparente (modal).
     */
    public function subscribe(string $planId, string $period, SubscriptionService $service)
    {
        $teacher = auth()->user()->teacher;

        $plan = SaasPlan::query()
            ->where('active', true)
            ->find($planId)
            ?? abort(404);

        $billingPeriod = BillingPeriod::from($period);

        if (! $plan->isFree()) {
            $this->openPaymentForm($plan, $billingPeriod);

            return;
        }

        $service->activateFreePlan($teacher, $plan, $billingPeriod);

        $this->toastSuccess('Plano gratuito ativado com sucesso!');

        return $this->redirectRoute('subscription.show');
    }

    /**
     * Finaliza a assinatura com o cartão (checkout transparente).
     * Recebe o cartão já criptografado pelo navegador; o CPF vem da
     * propriedade `cpf` (form) com fallback no payload.
     *
     * @param  array<string, mixed>  $card
     * @return array<string, mixed>|void
     */
    public function payWithCard(array $card, SubscriptionService $service)
    {
        $teacher = auth()->user()->teacher;

        $plan = $this->payablePlan();

        $data = $this->validateWith(PayWithCardRequest::class, [
            'encrypted' => $card['encrypted'] ?? null,
            'holder' => $card['holder'] ?? null,
            'installments' => $card['installments'] ?? null,
            'cpf' => preg_replace('/\D/', '', (string) (($card['cpf'] ?? '') ?: $this->cpf)),
        ]);

        try {
            $result = $service->payWithCard($teacher, $plan, BillingPeriod::from($this->payPeriod), $data);
        } catch (\Throwable $e) {
            report($e);

            $message = $e instanceof \RuntimeException
                ? $e->getMessage()
                : 'Não foi possível processar o pagamento. Tente novamente ou contate o suporte.';

            $this->toastError($message);

            // Devolve ao formulário de cartão para o usuário corrigir e tentar de novo.
            return ['status' => 'error', 'message' => $message];
        }

        if ($result['status'] === 'approved') {
            $this->showPaymentForm = false;
            $this->toastSuccess('Pagamento aprovado! Assinatura ativada.');

            return $this->redirectRoute('subscription.show');
        }

        if ($result['status'] === 'pending') {
            $this->showPaymentForm = false;
            $this->toastInfo('Pagamento em análise. A assinatura será ativada assim que o banco confirmar.');

            return $this->redirectRoute('subscription.show');
        }

        // Recusado: modal aberto para o usuário tentar de novo com outro cartão.
        $message = $result['message'] ?? 'Pagamento recusado. Confira os dados ou use outro cartão.';
        $this->toastError($message);

        return ['status' => 'rejected', 'message' => $message];
    }

    /**
     * Gera o QR Code Pix do plano/período escolhido (a assinatura fica
     * aguardando pagamento) e exibe o QR no próprio modal.
     */
    public function payWithPix(SubscriptionService $service): void
    {
        $teacher = auth()->user()->teacher;

        $plan = $this->payablePlan();

        try {
            $result = $service->payWithPix($teacher, $plan, BillingPeriod::from($this->payPeriod));
        } catch (\Throwable $e) {
            report($e);

            $this->toastError($e instanceof \RuntimeException
                ? $e->getMessage()
                : 'Não foi possível gerar o QR Code Pix. Tente novamente ou contate o suporte.');

            return;
        }

        $this->pixGenerated = true;
        $this->pixCode = $result['qr_code'];

        $this->toastInfo('QR Code gerado. Pague pelo app do seu banco para ativar a assinatura.');
    }

    /**
     * Confere no gateway se o pagamento (Pix ou cartão em análise) já foi
     * compensado — o webhook pode não chegar em ambiente local.
     */
    public function checkPayment(SubscriptionService $service): void
    {
        $subscription = auth()->user()->teacher->subscription;

        if (! $subscription?->payment_id) {
            $this->toastInfo('Nenhum pagamento em andamento no momento.');

            return;
        }

        $updated = $service->syncFromOrder((string) $subscription->payment_id);

        if ($updated?->isCurrentlyActive()) {
            $this->showPaymentForm = false;
            $this->toastSuccess('Pagamento confirmado! Assinatura ativada.');
            $this->redirectRoute('subscription.show');

            return;
        }

        $this->toastInfo('Ainda não identificamos o pagamento. Se você acabou de pagar, aguarde alguns instantes e tente novamente.');
    }

    /** Fecha o modal do checkout (não altera a assinatura — nada foi cobrado). */
    public function cancelPayment(): void
    {
        $this->showPaymentForm = false;
        $this->cpf = '';
        $this->pixGenerated = false;
        $this->pixCode = '';
        $this->resetErrorBag();
    }

    /** Alterna a aba do meio de pagamento dentro do modal. */
    public function setPayMethod(string $method): void
    {
        $this->payMethod = in_array($method, ['card', 'pix'], true) ? $method : 'card';
    }

    /**
     * Prepara o modal de pagamento: estado server-side do plano escolhido
     * + chave pública para o navegador criptografar o cartão.
     * Nada é gravado em `subscriptions` até o pagamento ser enviado.
     */
    protected function openPaymentForm(SaasPlan $plan, BillingPeriod $period): void
    {
        $this->payPlanId = (string) $plan->id;
        $this->payPeriod = $period->value;
        $this->payAmount = $plan->priceFor($period);
        $this->payPlanName = $plan->name;
        $this->payMethod = 'card';
        $this->cpf = '';
        $this->pixGenerated = false;
        $this->pixCode = '';
        $this->resetErrorBag();

        // Sem a chave pública não há como criptografar o cartão no navegador
        // (o Pix continua disponível e não depende dela).
        $this->pagbankPublicKey = (string) (app(PagBankService::class)->publicKey() ?? '');

        $this->showPaymentForm = true;
    }

    /** Plano do pagamento em andamento (nunca vem do request). */
    protected function payablePlan(): SaasPlan
    {
        return SaasPlan::query()
            ->where('active', true)
            ->find($this->payPlanId)
            ?? abort(404);
    }

    #[Title('Minha assinatura')]
    public function render()
    {
        $teacher = auth()->user()->teacher;

        return view('livewire.dashboard.my-subscription', [
            'subscription' => $teacher->subscription,
            'plans' => SaasPlan::query()
                ->where('active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'periodLabels' => BillingPeriod::labels(),
            'statusLabels' => SubscriptionStatus::labels(),
            // QR desenhado aqui (SVG) a partir do copia e cola: o modal não
            // aponta para nenhuma URL do parceiro de pagamentos.
            'pixQrCode' => $this->pixCode !== ''
                ? QrCode::size(200)->margin(1)->generate($this->pixCode)
                : null,
            'badgeClasses' => [
                'pending' => 'badge-info',
                'trial' => 'badge-info',
                'active' => 'badge-success',
                'past_due' => 'badge-warning',
                'cancelled' => 'badge-secondary',
                'expired' => 'badge-danger',
            ],
        ]);
    }
}
