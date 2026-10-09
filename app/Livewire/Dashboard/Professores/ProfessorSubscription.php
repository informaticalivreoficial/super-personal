<?php

namespace App\Livewire\Dashboard\Professores;

use App\Enums\SubscriptionStatus;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Services\SubscriptionService;
use App\Traits\ValidatesWithFormRequest;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Card de assinatura do treinador (billing manual da plataforma) —
 * aninhado no ProfessorForm (edição); gestão exclusiva do admin.
 */
class ProfessorSubscription extends Component
{
    use ValidatesWithFormRequest, WithToastr;

    public int $teacherId = 0;

    public int $subscriptionId = 0;

    public string $plan = 'basic';

    public string $status = 'trial';

    public $amount = '';

    public $starts_at = '';

    public $trial_ends_at = '';

    public $ends_at = '';

    /** @var array<string, string> */
    public array $statusLabels = [];

    public function mount($teacher = null): void
    {
        if (is_string($teacher) || is_int($teacher)) {
            $teacher = Teacher::find($teacher) ?? abort(404);
        }

        abort_unless($teacher instanceof Teacher, 404);

        Gate::authorize('update', $teacher);

        $this->teacherId = $teacher->id;
        $this->statusLabels = SubscriptionStatus::labels();

        if ($subscription = $teacher->subscription) {
            $this->subscriptionId = $subscription->id;
            $this->plan = $subscription->plan;
            $this->status = $subscription->status->value;
            $this->amount = (string) $subscription->amount;
            $this->starts_at = $subscription->starts_at?->toDateString() ?? '';
            $this->trial_ends_at = $subscription->trial_ends_at?->toDateString() ?? '';
            $this->ends_at = $subscription->ends_at?->toDateString() ?? '';
        }
    }

    public function save(SubscriptionService $service)
    {
        $teacher = Teacher::find($this->teacherId) ?? abort(404);
        Gate::authorize('update', $teacher);

        $data = [
            'plan' => $this->plan,
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

    #[Title('Assinatura')]
    public function render()
    {
        return view('livewire.dashboard.professores.subscription');
    }
}
