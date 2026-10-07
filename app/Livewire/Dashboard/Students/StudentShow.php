<?php

namespace App\Livewire\Dashboard\Students;

use App\Enums\PaymentStatus;
use App\Enums\StudentGender;
use App\Enums\StudentLevel;
use App\Enums\TrainingPlanStatus;
use App\Models\Student;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
class StudentShow extends Component
{
    public Student $student;

    public function mount(Student $student): void
    {
        Gate::authorize('view', $student);

        $this->student = $student;
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
