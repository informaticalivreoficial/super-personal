<?php

namespace App\Livewire\Dashboard;

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Models\SaasPlan;
use App\Services\SubscriptionService;
use App\Traits\WithToastr;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Página "Minha assinatura" do treinador: consulta a própria assinatura
 * e assina/alterna planos do catálogo (gratuito ativa na hora; pago via
 * Mercado Pago Checkout Pro). O admin continua podendo ajustar manualmente
 * no card ProfessorSubscription.
 */
class MySubscription extends Component
{
    use WithToastr;

    public function mount(): void
    {
        // Só o treinador chega aqui (middleware role:teacher); garante perfil.
        abort_unless(auth()->user()?->teacher, 403, 'Perfil de treinador não encontrado.');
    }

    /**
     * Assina (ou troca para) o plano no período indicado.
     * Gratuito → ativa na hora; pago → redireciona para o checkout Mercado Pago.
     */
    public function subscribe(string $planId, string $period, SubscriptionService $service)
    {
        $teacher = auth()->user()->teacher;

        $plan = SaasPlan::query()
            ->where('active', true)
            ->find($planId)
            ?? abort(404);

        $result = $service->subscribe($teacher, $plan, BillingPeriod::from($period));

        if ($result['checkout_url']) {
            return $this->redirect($result['checkout_url']);
        }

        $this->toastSuccess(
            $plan->isFree()
                ? 'Plano gratuito ativado com sucesso!'
                : 'Assinatura criada — aguardando pagamento.'
        );

        return $this->redirectRoute('subscription.show');
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
