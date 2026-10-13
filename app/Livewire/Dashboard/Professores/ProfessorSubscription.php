<?php

namespace App\Livewire\Dashboard\Professores;

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Services\SubscriptionService;
use App\Traits\ValidatesWithFormRequest;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Card de assinatura do treinador (billing da plataforma) —
 * aninhado no ProfessorForm (edição); gestão exclusiva do admin.
 * Plano vem do catálogo saas_plans; pagamento pode ser manual ou via MP.
 */
class ProfessorSubscription extends Component
{
    use ValidatesWithFormRequest, WithToastr;

    public int $teacherId = 0;

    public int $subscriptionId = 0;

    public string $plan_id = '';

    public string $period = 'monthly';

    public string $status = 'active';

    public $amount = '';

    public $starts_at = '';

    public $trial_ends_at = '';

    public $ends_at = '';

    /** @var array<string, string> */
    public array $statusLabels = [];

    /** @var array<string, string> */
    public array $periodLabels = [];

    /** @var array<int, array{id: int, name: string, label: string}> */
    public array $plans = [];

    public function mount($teacher = null): void
    {
        if (is_string($teacher) || is_int($teacher)) {
            $teacher = Teacher::find($teacher) ?? abort(404);
        }

        abort_unless($teacher instanceof Teacher, 404);

        Gate::authorize('update', $teacher);

        $this->teacherId = $teacher->id;
        $this->statusLabels = SubscriptionStatus::labels();
        $this->periodLabels = BillingPeriod::labels();
        $this->plans = $this->planOptions();

        if ($subscription = $teacher->subscription) {
            $this->subscriptionId = $subscription->id;
            $this->plan_id = (string) $subscription->plan_id;
            $this->period = $subscription->period?->value ?? 'monthly';
            $this->status = $subscription->status->value;
            $this->amount = (string) $subscription->amount;
            $this->starts_at = $subscription->starts_at?->toDateString() ?? '';
            $this->trial_ends_at = $subscription->trial_ends_at?->toDateString() ?? '';
            $this->ends_at = $subscription->ends_at?->toDateString() ?? '';
        }
    }

    /** Ao escolher plano/período, sugere o valor da tabela do plano. */
    public function updated($property): void
    {
        if (! in_array($property, ['plan_id', 'period'], true)) {
            return;
        }

        $plan = SaasPlan::find($this->plan_id);

        if ($plan) {
            $this->amount = (string) $plan->priceFor(BillingPeriod::from($this->period));
        }
    }

    public function save(SubscriptionService $service)
    {
        $teacher = Teacher::find($this->teacherId) ?? abort(404);
        Gate::authorize('update', $teacher);

        $data = [
            'plan_id' => (int) $this->plan_id,
            'period' => $this->period,
            'status' => $this->status,
            'amount' => $this->amount === '' ? null : $this->amount,
            'starts_at' => $this->starts_at ?: null,
            'trial_ends_at' => $this->trial_ends_at ?: null,
            'ends_at' => $this->ends_at ?: null,
        ];

        $validated = $this->validateWith(StoreSubscriptionRequest::class, $data);

        if ($this->subscriptionId) {
            $subscription = Subscription::find($this->subscriptionId) ?? abort(404);
            $service->update($subscription, $validated);

            $this->toastSuccess('Assinatura atualizada com sucesso.');
        } else {
            $service->store($teacher, $validated);

            $this->toastSuccess('Assinatura criada com sucesso.');
        }
    }

    /** @return array<int, array{id: int, name: string, label: string}> */
    private function planOptions(): array
    {
        return SaasPlan::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (SaasPlan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'label' => sprintf('%s — %s alunos — R$ %s/mês', $plan->name, $plan->maxStudentsLabel(), number_format((float) $plan->price_monthly, 2, ',', '.')),
            ])
            ->all();
    }

    #[Title('Assinatura')]
    public function render()
    {
        return view('livewire.dashboard.professores.subscription');
    }
}
