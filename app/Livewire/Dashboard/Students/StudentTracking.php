<?php

namespace App\Livewire\Dashboard\Students;

use App\Enums\TrainingExecutionStatus;
use App\Enums\TrainingSessionStatus;
use App\Http\Requests\StoreProgressRequest;
use App\Models\Student;
use App\Models\StudentProgress;
use App\Services\StudentProgressService;
use App\Traits\ValidatesWithFormRequest;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Acompanhamento do aluno: execuções dos treinos, aderência ao plano
 * ativo e histórico de avaliações físicas (student_progress).
 */
#[Layout('components.layouts.app')]
class StudentTracking extends Component
{
    use ValidatesWithFormRequest, WithPagination, WithToastr;

    protected $paginationTheme = 'tailwind';

    public Student $student;

    public bool $showForm = false;

    // Formulário de nova avaliação física (StoreProgressRequest)
    public $recorded_at;

    public $weight;

    public $body_fat;

    public $resting_heart_rate;

    public $max_heart_rate;

    public $ftp;

    public $running_pace;

    public $swimming_pace;

    public $notes;

    public function mount(Student $student): void
    {
        Gate::authorize('view', $student);

        $this->student = $student;
        $this->recorded_at = now()->toDateString();
    }

    public function toggleForm(): void
    {
        $this->showForm = ! $this->showForm;
    }

    public function saveProgress(StudentProgressService $service): void
    {
        Gate::authorize('create', [StudentProgress::class, $this->student]);

        $validated = $this->validateWith(StoreProgressRequest::class, [
            'recorded_at' => $this->recorded_at,
            'weight' => $this->weight,
            'body_fat' => $this->body_fat,
            'resting_heart_rate' => $this->resting_heart_rate,
            'max_heart_rate' => $this->max_heart_rate,
            'ftp' => $this->ftp,
            'running_pace' => $this->running_pace,
            'swimming_pace' => $this->swimming_pace,
            'notes' => $this->notes,
        ]);

        $service->store($this->student, $validated);

        $this->toastSuccess('Avaliação registrada com sucesso.');
        $this->resetForm();
        $this->resetPage('avaliacoes');
        $this->showForm = false;
    }

    public function resetForm(): void
    {
        $this->recorded_at = now()->toDateString();
        $this->weight = null;
        $this->body_fat = null;
        $this->resting_heart_rate = null;
        $this->max_heart_rate = null;
        $this->ftp = null;
        $this->running_pace = null;
        $this->swimming_pace = null;
        $this->notes = null;
        $this->resetValidation();
    }

    #[Title('Acompanhamento do Aluno')]
    public function render()
    {
        $student = $this->student;

        $activePlan = $student->trainingPlans()
            ->where('status', 'active')
            ->first();

        // Aderência: concluídas / (total sem canceladas) do plano ativo
        $adherence = null;
        $planSessions = null;
        $planCompleted = null;
        $planSkipped = null;

        if ($activePlan) {
            // Colunas qualificadas: training_weeks também tem `status` (join ambíguo)
            $planSessions = $activePlan->sessions()
                ->where('training_sessions.status', '!=', TrainingSessionStatus::CANCELLED->value)
                ->count();
            $planCompleted = $activePlan->sessions()
                ->where('training_sessions.status', TrainingSessionStatus::COMPLETED->value)
                ->count();
            $planSkipped = $activePlan->sessions()
                ->where('training_sessions.status', TrainingSessionStatus::SKIPPED->value)
                ->count();

            if ($planSessions > 0) {
                $adherence = (int) round($planCompleted / $planSessions * 100);
            }
        }

        $executions = $student->executions()
            ->with('session.sport')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'execucoes');

        $progress = $student->progress()
            ->paginate(10, ['*'], 'avaliacoes');

        $lastExecution = $student->executions()->orderByDesc('id')->first();

        $completedThisMonth = $student->executions()
            ->where('status', TrainingExecutionStatus::COMPLETED)
            ->whereMonth('completed_at', now()->month)
            ->whereYear('completed_at', now()->year)
            ->count();

        return view('livewire.dashboard.students.tracking', [
            'activePlan' => $activePlan,
            'adherence' => $adherence,
            'planSessions' => $planSessions,
            'planCompleted' => $planCompleted,
            'planSkipped' => $planSkipped,
            'executions' => $executions,
            'progress' => $progress,
            'lastExecution' => $lastExecution,
            'completedThisMonth' => $completedThisMonth,
            'totalExecutions' => $student->executions()->count(),
            'executionStatus' => TrainingExecutionStatus::labels(),
        ]);
    }
}
