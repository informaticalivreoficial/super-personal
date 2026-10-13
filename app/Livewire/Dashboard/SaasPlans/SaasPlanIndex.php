<?php

namespace App\Livewire\Dashboard\SaasPlans;

use App\Models\SaasPlan;
use App\Services\SaasPlanService;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Lista dos planos do SaaS (catálogo da plataforma — admin only).
 */
#[Layout('components.layouts.app')]
class SaasPlanIndex extends Component
{
    use WithPagination, WithToastr;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public $deleteId = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', SaasPlan::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete($planId): void
    {
        $plan = SaasPlan::find($planId) ?? abort(404);
        Gate::authorize('delete', $plan);

        $this->deleteId = $plan->id;
    }

    public function delete(SaasPlanService $service): void
    {
        $plan = SaasPlan::find($this->deleteId);

        if ($plan) {
            Gate::authorize('delete', $plan);

            try {
                $service->delete($plan);
                $this->toastSuccess('Plano excluído com sucesso.');
            } catch (ValidationException $e) {
                $this->toastError($e->getMessage());
            }
        }

        $this->deleteId = null;
    }

    #[Title('Planos do SaaS')]
    public function render()
    {
        $plans = SaasPlan::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'LIKE', "%{$this->search}%")
                        ->orWhere('slug', 'LIKE', "%{$this->search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.dashboard.saas-plans.index', [
            'plans' => $plans,
        ]);
    }
}
