<?php

namespace Tests\Feature\Panel;

use App\Enums\TrainingSessionStatus;
use App\Livewire\Dashboard\Students\StudentTracking;
use App\Models\Student;
use App\Models\StudentProgress;
use App\Models\Teacher;
use App\Models\TrainingExecution;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\TrainingWeek;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentTrackingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{teacher: Teacher, student: Student}
     */
    private function arrangeStudentWithPlan(): array
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $plan = TrainingPlan::factory()->forStudent($student)->create();
        $week = TrainingWeek::factory()->forPlan($plan, 1)->create();

        return [$teacher, $student, $plan, $week];
    }

    public function test_teacher_views_tracking_page_with_summary(): void
    {
        [$teacher, $student, $plan, $week] = $this->arrangeStudentWithPlan();

        $done = TrainingSession::factory()->forWeek($week, [
            'status' => TrainingSessionStatus::COMPLETED,
            'title' => 'Sessão concluída de teste',
        ])->create();
        TrainingSession::factory()->forWeek($week, ['status' => TrainingSessionStatus::PLANNED])->create();

        TrainingExecution::factory()->forSession($done)->create();
        StudentProgress::factory()->forStudent($student)->create(['recorded_at' => '2026-10-01']);

        $this->actingAs($teacher->user);

        $this->get(route('students.tracking', $student))
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee('Acompanhamento')
            ->assertSee('Aderência ao plano')
            ->assertSee('50%')
            ->assertSee('Sessão concluída de teste')
            ->assertSee('Histórico de avaliações')
            ->assertSee('Execuções dos treinos')
            ->assertSee('01/10/2026');
    }

    public function test_tracking_without_data_shows_empty_states(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        $this->get(route('students.tracking', $student))
            ->assertOk()
            ->assertSee('Sem plano ativo')
            ->assertSee('Nenhuma avaliação registrada para este aluno.')
            ->assertSee('Nenhuma execução registrada');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->get(route('students.tracking', $student))
            ->assertRedirect('/auth/login');
    }

    public function test_student_role_is_blocked_from_tracking(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->actingAs($student->user);

        $this->get(route('students.tracking', $student))->assertForbidden();
    }

    public function test_student_from_another_tenant_returns_404(): void
    {
        $teacherA = Teacher::factory()->create();
        $studentB = Student::factory()->forTeacher(Teacher::factory()->create())->create();

        $this->actingAs($teacherA->user);

        $this->get(route('students.tracking', $studentB))->assertNotFound();
    }

    public function test_teacher_registers_progress(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        Livewire::test(StudentTracking::class, ['student' => $student])
            ->set('recorded_at', '2026-10-05')
            ->set('weight', 78.5)
            ->set('body_fat', 18.2)
            ->set('resting_heart_rate', 52)
            ->set('ftp', 245)
            ->set('running_pace', '5:10/km')
            ->set('notes', 'Boa evolução')
            ->call('saveProgress')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('student_progress', [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'weight' => 78.5,
            'body_fat' => 18.2,
            'resting_heart_rate' => 52,
            'ftp' => 245,
            'notes' => 'Boa evolução',
        ]);

        $this->assertSame(
            '2026-10-05',
            StudentProgress::query()
                ->where('student_id', $student->id)
                ->first()?->recorded_at?->toDateString()
        );
    }

    public function test_progress_requires_recorded_at(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        Livewire::test(StudentTracking::class, ['student' => $student])
            ->set('recorded_at', null)
            ->call('saveProgress')
            ->assertHasErrors(['recorded_at']);

        $this->assertDatabaseCount('student_progress', 0);
    }

    public function test_progress_validates_numeric_ranges(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        Livewire::test(StudentTracking::class, ['student' => $student])
            ->set('recorded_at', '2026-10-05')
            ->set('weight', 500)
            ->set('body_fat', 95)
            ->call('saveProgress')
            ->assertHasErrors(['weight', 'body_fat']);
    }

    public function test_component_denies_student_of_another_tenant(): void
    {
        $teacherA = Teacher::factory()->create();
        $studentB = Student::factory()->forTeacher(Teacher::factory()->create())->create();

        $this->actingAs($teacherA->user);
        $this->withoutExceptionHandling();

        $this->expectException(AuthorizationException::class);

        Livewire::test(StudentTracking::class, ['student' => $studentB]);
    }

    public function test_admin_registers_progress_preserving_student_ownership(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        Livewire::test(StudentTracking::class, ['student' => $student])
            ->set('recorded_at', '2026-10-06')
            ->set('weight', 70)
            ->call('saveProgress')
            ->assertHasNoErrors();

        // teacher_id preservado do aluno (não vira null por ser admin)
        $this->assertDatabaseHas('student_progress', [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'weight' => 70,
        ]);
    }

    public function test_tracking_page_is_smokeable_from_student_show_link(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        $response = $this->get(route('students.show', $student));
        $response->assertOk()->assertSee(route('students.tracking', $student), false);
    }
}
