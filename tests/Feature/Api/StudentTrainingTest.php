<?php

namespace Tests\Feature\Api;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\TrainingExecution;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\TrainingWeek;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentTrainingTest extends TestCase
{
    use RefreshDatabase;

    /** @var array{teacher: Teacher, mine: Student, other: Student} */
    private array $ctx;

    protected function setUp(): void
    {
        parent::setUp();

        $teacher = Teacher::factory()->create();
        $mine = Student::factory()->forTeacher($teacher)->create();
        $other = Student::factory()->forTeacher($teacher)->create();

        $this->ctx = compact('teacher', 'mine', 'other');
    }

    private function sessionFor(Student $student): TrainingSession
    {
        $plan = TrainingPlan::factory()->forStudent($student)->create();
        $week = TrainingWeek::factory()->forPlan($plan)->create();

        return TrainingSession::factory()->forWeek($week)->create([
            'student_id' => $student->id,
            'teacher_id' => $student->teacher_id,
        ]);
    }

    public function test_student_sees_only_own_trainings(): void
    {
        $mineSession = $this->sessionFor($this->ctx['mine']);
        $this->sessionFor($this->ctx['other']);

        Sanctum::actingAs($this->ctx['mine']->user);

        $response = $this->getJson('/api/v1/student/trainings');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame($mineSession->id, $response->json('data.0.id'));
    }

    public function test_student_cannot_view_another_students_training(): void
    {
        $otherSession = $this->sessionFor($this->ctx['other']);

        Sanctum::actingAs($this->ctx['mine']->user);

        $this->getJson("/api/v1/student/trainings/{$otherSession->id}")->assertStatus(403);
    }

    public function test_student_completes_own_training_with_execution(): void
    {
        $session = $this->sessionFor($this->ctx['mine']);

        Sanctum::actingAs($this->ctx['mine']->user);

        $response = $this->postJson("/api/v1/student/trainings/{$session->id}/complete", [
            'duration' => 3600,
            'distance' => 12000,
            'perceived_effort' => 7,
            'notes' => 'Treino concluído.',
        ]);

        $response->assertStatus(201);
        $this->assertSame($this->ctx['mine']->id, $response->json('data.student_id'));

        $this->assertDatabaseHas('training_executions', [
            'training_session_id' => $session->id,
            'student_id' => $this->ctx['mine']->id,
            'duration' => 3600,
            'distance' => 12000,
            'perceived_effort' => 7,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('training_sessions', [
            'id' => $session->id,
            'status' => 'completed',
        ]);
    }

    public function test_student_cannot_complete_another_students_training(): void
    {
        $otherSession = $this->sessionFor($this->ctx['other']);

        Sanctum::actingAs($this->ctx['mine']->user);

        $this->postJson("/api/v1/student/trainings/{$otherSession->id}/complete", [
            'duration' => 3600,
        ])->assertStatus(403);
    }

    public function test_teacher_views_execution_of_own_student(): void
    {
        $session = $this->sessionFor($this->ctx['mine']);
        $execution = TrainingExecution::factory()->forSession($session)->create();

        Sanctum::actingAs($this->ctx['teacher']->user);

        $this->getJson("/api/v1/teacher/students/{$this->ctx['mine']->id}/executions")
            ->assertOk()
            ->assertJsonPath('data.0.id', $execution->id);
    }

    public function test_teacher_cannot_view_execution_of_another_teachers_student(): void
    {
        $teacherB = Teacher::factory()->create();
        $studentB = Student::factory()->forTeacher($teacherB)->create();

        Sanctum::actingAs($this->ctx['teacher']->user);

        $this->getJson("/api/v1/teacher/students/{$studentB->id}/executions")->assertStatus(404);
    }

    public function test_student_cannot_access_teacher_endpoints(): void
    {
        Sanctum::actingAs($this->ctx['mine']->user);

        $this->getJson('/api/v1/teacher/students')->assertStatus(403);
        $this->postJson("/api/v1/teacher/students/{$this->ctx['mine']->id}/training-plans", [])->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_list_trainings(): void
    {
        $this->getJson('/api/v1/student/trainings')->assertStatus(401);
    }
}
