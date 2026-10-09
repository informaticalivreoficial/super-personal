<?php

namespace App\Livewire\Dashboard\Plans;

use App\Enums\TrainingPlanStatus;
use App\Http\Requests\StoreTrainingPlanRequest;
use App\Http\Requests\UpdateTrainingPlanRequest;
use App\Models\Student;
use App\Models\TrainingPlan;
use App\Services\TrainingPlanService;
use App\Traits\ValidatesWithFormRequest;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Formulário único de criação/edição de plano de treino.
 * As rotas `plans.create` e `plans.edit` apontam para este componente.
 */
#[Layout('components.layouts.app')]
class PlanForm extends Component
{
    use ValidatesWithFormRequest, WithToastr;

    public $planId = null;

    public $student_id = '';

    public $name = '';

    public $description = '';

    public $goal = '';

    public $start_date = '';

    public $end_date = '';

    public $status = 'draft';

    public $notes = '';

    public function mount($plan = null): void
    {
        if (is_string($plan) || is_int($plan)) {
            $plan = TrainingPlan::find($plan) ?? abort(404);
        }

        if ($plan instanceof TrainingPlan) {
            Gate::authorize('update', $plan);

            $this->planId = $plan->id;
            $this->student_id = $plan->student_id;
            $this->name = $plan->name;
            $this->description = $plan->description;
            $this->goal = $plan->goal;
            $this->start_date = $plan->start_date?->format('Y-m-d');
            $this->end_date = $plan->end_date?->format('Y-m-d');
            $this->status = $plan->status->value;
            $this->notes = $plan->notes;

            return;
        }

        Gate::authorize('create', TrainingPlan::class);
    }

    public function save(TrainingPlanService $service)
    {
        $data = $this->formData();

        if ($this->planId) {
            $plan = TrainingPlan::findOrFail($this->planId);
            Gate::authorize('update', $plan);

            $validated = $this->validateWith(UpdateTrainingPlanRequest::class, $data);

            $service->update($plan, $validated);

            session()->flash('toast', [
                'type' => 'success',
                'message' => 'Plano atualizado com sucesso.',
            ]);

            return $this->redirectRoute('plans.show', ['plan' => $plan->id]);
        }

        Gate::authorize('create', TrainingPlan::class);

        // Escopo do tenant: aluno inexistente/de outro treinador → null.
        $student = Student::find($this->student_id);

        if (! $student) {
            throw ValidationException::withMessages([
                'student_id' => 'Selecione um aluno válido.',
            ]);
        }

        $validated = $this->validateWith(StoreTrainingPlanRequest::class, $data);
        $plan = $service->store($student, $validated);

        session()->flash('toast', [
            'type' => 'success',
            'message' => 'Plano criado com sucesso.',
        ]);

        return $this->redirectRoute('plans.show', ['plan' => $plan->id]);
    }

    public function cancel()
    {
        if ($this->planId) {
            return $this->redirectRoute('plans.show', ['plan' => $this->planId]);
        }

        return $this->redirectRoute('plans.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description ?: null,
            'goal' => $this->goal ?: null,
            'start_date' => $this->start_date ?: null,
            'end_date' => $this->end_date ?: null,
            'status' => $this->status ?: null,
            'notes' => $this->notes ?: null,
        ];
    }

    #[Title('Formulário de Plano')]
    public function render()
    {
        return view('livewire.dashboard.plans.form', [
            'students' => Student::orderBy('name')->get(),
            'statuses' => TrainingPlanStatus::labels(),
            'isEdit' => (bool) $this->planId,
            'planStudent' => $this->planId
                ? TrainingPlan::with('student')->findOrFail($this->planId)->student
                : null,
        ]);
    }
}
