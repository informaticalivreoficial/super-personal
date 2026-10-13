<?php

namespace App\Livewire\Dashboard\SaasPlans;

use App\Http\Requests\SaasPlanRequest;
use App\Models\SaasPlan;
use App\Services\SaasPlanService;
use App\Traits\ValidatesWithFormRequest;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Formulário único de criação/edição de planos do SaaS.
 * As rotas `saas-plans.create` e `saas-plans.edit` apontam para este componente.
 */
#[Layout('components.layouts.app')]
class SaasPlanForm extends Component
{
    use ValidatesWithFormRequest, WithToastr;

    public $planId = null;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public string $max_students = '';

    public string $price_monthly = '';

    public string $price_quarterly = '';

    public string $price_annual = '';

    public bool $active = true;

    public string $sort_order = '0';

    public function mount($plan = null): void
    {
        if (is_string($plan) || is_int($plan)) {
            $plan = SaasPlan::find($plan) ?? abort(404);
        }

        if ($plan instanceof SaasPlan) {
            Gate::authorize('update', $plan);

            $this->planId = $plan->id;
            $this->name = $plan->name;
            $this->slug = $plan->slug;
            $this->description = (string) $plan->description;
            $this->max_students = $plan->max_students === null ? '' : (string) $plan->max_students;
            $this->price_monthly = (string) $plan->price_monthly;
            $this->price_quarterly = (string) $plan->price_quarterly;
            $this->price_annual = (string) $plan->price_annual;
            $this->active = $plan->active;
            $this->sort_order = (string) $plan->sort_order;

            return;
        }

        Gate::authorize('create', SaasPlan::class);
    }

    public function save(SaasPlanService $service)
    {
        $data = [
            'plan_id' => $this->planId,
            'name' => $this->name,
            'slug' => $this->slug ?: null,
            'description' => $this->description ?: null,
            'max_students' => $this->max_students === '' ? null : (int) $this->max_students,
            'price_monthly' => $this->price_monthly === '' ? 0 : (float) $this->price_monthly,
            'price_quarterly' => $this->price_quarterly === '' ? 0 : (float) $this->price_quarterly,
            'price_annual' => $this->price_annual === '' ? 0 : (float) $this->price_annual,
            'active' => $this->active,
            'sort_order' => (int) $this->sort_order,
        ];

        if ($this->planId) {
            $plan = SaasPlan::find($this->planId) ?? abort(404);
            Gate::authorize('update', $plan);

            $validated = $this->validateWith(SaasPlanRequest::class, $data);

            $service->update($plan, $validated);

            $this->toastSuccess('Plano atualizado com sucesso.');

            return $this->redirectRoute('saas-plans.index');
        }

        Gate::authorize('create', SaasPlan::class);

        $validated = $this->validateWith(SaasPlanRequest::class, $data);

        $service->store($validated);

        $this->toastSuccess('Plano criado com sucesso.');

        return $this->redirectRoute('saas-plans.index');
    }

    public function cancel()
    {
        return $this->redirectRoute('saas-plans.index');
    }

    #[Title('Formulário de Plano')]
    public function render()
    {
        return view('livewire.dashboard.saas-plans.form', [
            'isEdit' => (bool) $this->planId,
        ]);
    }
}
