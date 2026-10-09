<?php

namespace App\Livewire\Dashboard\Students;

use App\Enums\PaymentStatus;
use App\Enums\StudentGender;
use App\Enums\StudentLevel;
use App\Enums\TrainingPlanStatus;
use App\Models\Student;
use App\Services\StudentService;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
class StudentShow extends Component
{
    use WithToastr;

    public Student $student;

    public function mount(Student $student): void
    {
        Gate::authorize('view', $student);

        $this->student = $student;
    }

    /**
     * Gera um novo código de convite (aluno ainda sem conta de acesso no app).
     */
    public function regenerateInvite(): void
    {
        Gate::authorize('update', $this->student);

        app(StudentService::class)->regenerateInviteCode($this->student);
        $this->student->refresh();

        $this->toastSuccess('Novo código de convite gerado.');
    }

    #[Title('Detalhes do Aluno')]
    public function render()
    {
        $student = $this->student;

        $activePlan = $student->trainingPlans()
            ->where('status', TrainingPlanStatus::ACTIVE->value)
            ->first();

        $recentPayments = $student->payments()
            ->orderByDesc('due_date')
            ->limit(5)
            ->get();

        return view('livewire.dashboard.students.show', [
            'student' => $student,
            'activePlan' => $activePlan,
            'recentPayments' => $recentPayments,
            'genders' => StudentGender::labels(),
            'levels' => StudentLevel::labels(),
            'paymentStatus' => PaymentStatus::labels(),
        ]);
    }
}
