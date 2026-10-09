<?php

namespace Tests\Feature\Notifications;

use App\Enums\TrainingPlanStatus;
use App\Livewire\Dashboard\Plans\PlanForm;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TrainingPlan;
use App\Notifications\NewTrainingAvailable;
use App\Services\TrainingPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

class NewTrainingAvailableTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{teacher: Teacher, student: Student}
     */
    private function arrange(): array
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        return [$teacher, $student];
    }

    private function publishData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Preparação Triathlon',
            'start_date' => now()->toDateString(),
        ], $overrides);
    }

    public function test_creating_active_plan_notifies_student(): void
    {
        [, $student] = $this->arrange();

        $plan = app(TrainingPlanService::class)->store($student, $this->publishData([
            'status' => TrainingPlanStatus::ACTIVE,
        ]));

        $notification = DatabaseNotification::where('type', NewTrainingAvailable::class)->firstOrFail();

        $this->assertSame($student->user_id, $notification->notifiable_id);
        $this->assertSame($plan->id, $notification->data['plan_id']);
        $this->assertSame('Novo treino disponível', $notification->data['title']);
        $this->assertStringContainsString('Preparação Triathlon', $notification->data['message']);
    }

    public function test_creating_draft_plan_does_not_notify(): void
    {
        [, $student] = $this->arrange();

        // Sem status → default da migration = draft.
        app(TrainingPlanService::class)->store($student, $this->publishData());

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_publishing_draft_plan_notifies_student(): void
    {
        [, $student] = $this->arrange();
        $service = app(TrainingPlanService::class);

        $plan = $service->store($student, $this->publishData());
        $this->assertDatabaseCount('notifications', 0);

        $service->update($plan, ['status' => TrainingPlanStatus::ACTIVE]);

        $this->assertSame(1, DatabaseNotification::where('type', NewTrainingAvailable::class)->count());
    }

    public function test_editing_active_plan_does_not_notify_again(): void
    {
        [, $student] = $this->arrange();
        $plan = TrainingPlan::factory()->forStudent($student)->create([
            'status' => TrainingPlanStatus::ACTIVE,
        ]);

        app(TrainingPlanService::class)->update($plan, [
            'name' => 'Ajuste de treino',
            'status' => TrainingPlanStatus::ACTIVE,
        ]);

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_reactivating_plan_notifies_again(): void
    {
        [, $student] = $this->arrange();
        $service = app(TrainingPlanService::class);

        $plan = TrainingPlan::factory()->forStudent($student)->create([
            'status' => TrainingPlanStatus::ACTIVE,
        ]);

        // Ativo → concluído: sem aviso.
        $service->update($plan, ['status' => TrainingPlanStatus::COMPLETED]);
        $this->assertDatabaseCount('notifications', 0);

        // Reativado: aluno avisado de novo.
        $service->update($plan->fresh(), ['status' => TrainingPlanStatus::ACTIVE]);
        $this->assertSame(1, DatabaseNotification::where('type', NewTrainingAvailable::class)->count());
    }

    public function test_student_without_account_is_skipped(): void
    {
        [, $student] = $this->arrange();
        $studentWithoutAccount = Student::factory()->withoutAccount()->create([
            'teacher_id' => $student->teacher_id,
        ]);

        app(TrainingPlanService::class)->store($studentWithoutAccount, $this->publishData([
            'status' => TrainingPlanStatus::ACTIVE,
        ]));

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_api_creating_active_plan_notifies_student(): void
    {
        [$teacher, $student] = $this->arrange();

        Sanctum::actingAs($teacher->user);

        $this->postJson("/api/v1/teacher/students/{$student->id}/training-plans", [
            'name' => 'Plano pela API',
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ])->assertStatus(201);

        $notification = DatabaseNotification::where('type', NewTrainingAvailable::class)->firstOrFail();

        $plan = TrainingPlan::where('name', 'Plano pela API')->firstOrFail();

        $this->assertSame($student->user_id, $notification->notifiable_id);
        $this->assertSame($plan->id, $notification->data['plan_id']);
        $this->assertStringContainsString('Plano pela API', $notification->data['message']);
    }

    public function test_panel_publishing_plan_notifies_student(): void
    {
        [$teacher, $student] = $this->arrange();
        $plan = TrainingPlan::factory()->forStudent($student)->create([
            'status' => TrainingPlanStatus::DRAFT,
        ]);

        $this->actingAs($teacher->user);

        Livewire::test(PlanForm::class, ['plan' => $plan])
            ->set('status', 'active')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, DatabaseNotification::where('type', NewTrainingAvailable::class)->count());
    }
}
