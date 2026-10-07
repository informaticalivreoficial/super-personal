<?php

namespace Tests\Feature\Api;

use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TrainingExecution;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\TrainingWeek;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function sessionFor(Student $student): TrainingSession
    {
        $plan = TrainingPlan::factory()->forStudent($student)->create();
        $week = TrainingWeek::factory()->forPlan($plan)->create();

        return TrainingSession::factory()->forWeek($week)->create([
            'student_id' => $student->id,
            'teacher_id' => $student->teacher_id,
        ]);
    }

    public function test_teacher_lists_only_own_students(): void
    {
        $teacherA = Teacher::factory()->create();
        $teacherB = Teacher::factory()->create();
        Student::factory()->forTeacher($teacherA)->count(2)->create();
        Student::factory()->forTeacher($teacherB)->count(3)->create();

        Sanctum::actingAs($teacherA->user);

        $response = $this->getJson('/api/v1/teacher/students');

        $response->assertOk()->assertJsonCount(2, 'data');
        foreach ($response->json('data') as $student) {
            $this->assertSame($teacherA->id, $student['teacher_id']);
        }
    }

    public function test_teacher_cannot_view_another_teachers_student(): void
    {
        $teacherA = Teacher::factory()->create();
        $studentB = Student::factory()->forTeacher(Teacher::factory()->create())->create();

        Sanctum::actingAs($teacherA->user);

        $this->getJson("/api/v1/teacher/students/{$studentB->id}")->assertStatus(404);
    }

    public function test_teacher_can_create_training_plan_for_own_student(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        Sanctum::actingAs($teacher->user);

        $this->postJson("/api/v1/teacher/students/{$student->id}/training-plans", [
            'name' => 'Preparação 5km',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeeks(8)->toDateString(),
        ])->assertStatus(201);

        $this->assertDatabaseHas('training_plans', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'name' => 'Preparação 5km',
        ]);
    }

    public function test_teacher_cannot_edit_another_teachers_plan(): void
    {
        $teacherA = Teacher::factory()->create();
        $studentB = Student::factory()->forTeacher(Teacher::factory()->create())->create();
        $planB = TrainingPlan::factory()->forStudent($studentB)->create();

        Sanctum::actingAs($teacherA->user);

        $this->putJson("/api/v1/teacher/training-plans/{$planB->id}", [
            'title' => 'Alterado indevidamente',
        ])->assertStatus(404);
    }

    public function test_teacher_views_executions_of_own_student(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $session = $this->sessionFor($student);
        TrainingExecution::factory()->forSession($session)->create();

        Sanctum::actingAs($teacher->user);

        $response = $this->getJson("/api/v1/teacher/students/{$student->id}/executions");

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame($student->id, $response->json('data.0.student_id'));
    }

    public function test_teacher_cannot_view_executions_of_another_teachers_student(): void
    {
        $teacherA = Teacher::factory()->create();
        $studentB = Student::factory()->forTeacher(Teacher::factory()->create())->create();

        Sanctum::actingAs($teacherA->user);

        $this->getJson("/api/v1/teacher/students/{$studentB->id}/executions")->assertStatus(404);
    }

    public function test_teacher_payments_are_scoped_to_tenant(): void
    {
        $teacherA = Teacher::factory()->create();
        $teacherB = Teacher::factory()->create();
        $studentA = Student::factory()->forTeacher($teacherA)->create();
        $studentB = Student::factory()->forTeacher($teacherB)->create();
        Payment::factory()->forStudent($studentB)->create();

        Sanctum::actingAs($teacherA->user);

        $this->getJson("/api/v1/teacher/students/{$studentB->id}/payments")->assertStatus(404);

        $this->postJson("/api/v1/teacher/students/{$studentA->id}/payments", [
            'description' => 'Mensalidade de outubro',
            'amount' => 250,
            'due_date' => now()->addDays(10)->toDateString(),
            'payment_method' => 'pix',
            'status' => 'pending',
        ])->assertStatus(201);

        $this->assertDatabaseHas('payments', [
            'student_id' => $studentA->id,
            'teacher_id' => $teacherA->id,
        ]);
    }
}
