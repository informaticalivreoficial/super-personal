<?php

namespace App\Livewire\Dashboard\Plans;

use App\Models\TrainingPlan;
use App\Services\TrainingPlanService;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class PlanIndex extends Component
{
    use WithPagination, WithToastr;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public $deleteId = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', TrainingPlan::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete($planId): void
    {
        $plan = TrainingPlan::find($planId) ?? abort(404);
        Gate::authorize('delete', $plan);

        $this->deleteId = $plan->id;
    }

    public function delete(TrainingPlanService $service): void
    {
        $plan = TrainingPlan::find($this->deleteId);

        if ($plan) {
            Gate::authorize('delete', $plan);
            $service->delete($plan);
            $this->toastSuccess('Plano excluído com sucesso.');
        }

        $this->deleteId = null;
    }

    #[Title('Planos de Treino')]
    public function render()
    {
        $plans = TrainingPlan::query()
            ->with('student')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'LIKE', "%{$this->search}%")
                        ->orWhere('goal', 'LIKE', "%{$this->search}%")
                        ->orWhereHas('student', function ($sq) {
                            $sq->where('name', 'LIKE', "%{$this->search}%");
                        });
                });
            })
            ->orderByDesc('start_date')
            ->paginate(15);

        return view('livewire.dashboard.plans.index', [
            'plans' => $plans,
        ]);
    }
}
