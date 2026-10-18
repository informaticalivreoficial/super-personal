<?php

namespace Tests\Feature\Panel;

use App\Enums\TrainingPlanStatus;
use App\Enums\TrainingSessionStatus;
use App\Livewire\Dashboard\Plans\PlanShow;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\TrainingSessionItem;
use App\Models\TrainingWeek;
use App\Notifications\NewTrainingAvailable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Duplicação de treino: plano inteiro para outro aluno e semana para o fim
 * do próprio plano (TrainingPlanService::duplicate/duplicateWeek).
 */
class PlanDuplicateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cenário padrão: treinador com dois alunos e um plano do aluno A com
     * duas semanas, duas sessões e composição.
     *
     * @return array{teacher: Teacher, studentA: Student, studentB: Student, plan: TrainingPlan}
     */
    private function arrange(): array
    {
        $teacher = Teacher::factory()->create();
        $studentA = Student::factory()->forTeacher($teacher)->create();
        $studentB = Student::factory()->forTeacher($teacher)->create();

        $sport = Sport::factory()->create();

        $plan = TrainingPlan::factory()->forStudent($studentA)->create([
            'name' => 'Periodização 12 semanas',
            'goal' => 'Meia maratona',
            'start_date' => '2026-10-05',
            'end_date' => '2026-11-15',
            'status' => TrainingPlanStatus::ACTIVE,
        ]);

        $week1 = TrainingWeek::factory()->forPlan($plan, 1)->create([
            'name' => 'Adaptação',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-11',
        ]);
        $week2 = TrainingWeek::factory()->forPlan($plan, 2)->create([
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-18',
        ]);

        $sessionA = TrainingSession::factory()->forWeek($week1, ['sport_id' => $sport->id])->create([
            'title' => 'Rodagem contínua',
            'status' => TrainingSessionStatus::PLANNED,
        ]);
        TrainingSession::factory()->forWeek($week1, ['sport_id' => $sport->id])->create([
            'title' => 'Intervalado',
            'status' => TrainingSessionStatus::COMPLETED,
        ]);
        TrainingSession::factory()->forWeek($week2, ['sport_id' => $sport->id])->create([
            'title' => 'Longão',
        ]);

        TrainingSessionItem::factory()->forSession($sessionA, 1)->create(['title' => 'Aquecimento']);
        TrainingSessionItem::factory()->forSession($sessionA, 2)->create(['title' => 'Bloco principal']);

        return compact('teacher', 'studentA', 'studentB', 'plan');
    }

    public function test_duplicates_plan_to_another_student_with_weeks_sessions_and_composition(): void
    {
        ['teacher' => $teacher, 'studentB' => $studentB, 'plan' => $plan] = $this->arrange();

        $this->actingAs($teacher->user);

        Livewire::test(PlanShow::class, ['plan' => $plan])
            ->call('openDuplicateForm')
            ->set('duplicateStudentId', $studentB->id)
            ->call('duplicatePlan')
            ->assertHasNoErrors()
            ->assertRedirect();

        $copy = TrainingPlan::withoutGlobalScopes()->where('student_id', $studentB->id)->first();

        $this->assertNotNull($copy);
        $this->assertSame($teacher->id, $copy->teacher_id);
        $this->assertSame('Periodização 12 semanas (cópia)', $copy->name);
        $this->assertSame('Meia maratona', $copy->goal);
        $this->assertSame(TrainingPlanStatus::DRAFT, $copy->status);
        $this->assertSame('2026-10-05', $copy->start_date->format('Y-m-d'));

        // 2 semanas e 3 sessões copiadas (origem intacta).
        $this->assertSame(2, TrainingWeek::withoutGlobalScopes()->where('training_plan_id', $copy->id)->count());
        $this->assertSame(3, TrainingSession::withoutGlobalScopes()->where('teacher_id', $teacher->id)->where('student_id', $studentB->id)->count());

        // Composição da primeira sessão da semana 1.
        $copiedWeek1 = TrainingWeek::withoutGlobalScopes()
            ->where('training_plan_id', $copy->id)->where('week_number', 1)->first();
        $copiedSession = TrainingSession::withoutGlobalScopes()
            ->where('training_week_id', $copiedWeek1->id)->where('title', 'Rodagem contínua')->first();

        $this->assertNotNull($copiedSession);
        $this->assertSame(2, TrainingSessionItem::where('training_session_id', $copiedSession->id)->count());
    }

    public function test_duplicate_resets_status_and_keeps_original_untouched(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA, 'studentB' => $studentB, 'plan' => $plan] = $this->arrange();

        $this->actingAs($teacher->user);

        Livewire::test(PlanShow::class, ['plan' => $plan])
            ->call('openDuplicateForm')
            ->set('duplicateStudentId', $studentB->id)
            ->call('duplicatePlan')
            ->assertHasNoErrors();

        // Sessão concluída na origem vira planejada na cópia.
        $completedOriginal = TrainingSession::withoutGlobalScopes()
            ->where('student_id', $studentA->id)->where('title', 'Intervalado')->first();
        $this->assertSame(TrainingSessionStatus::COMPLETED, $completedOriginal->status);

        $copy = TrainingSession::withoutGlobalScopes()
            ->where('student_id', $studentB->id)->where('title', 'Intervalado')->first();
        $this->assertSame(TrainingSessionStatus::PLANNED, $copy->status);

        // Origem intacta: continua ativa e com as 2 semanas.
        $this->assertSame(TrainingPlanStatus::ACTIVE, $plan->fresh()->status);
        $this->assertSame(2, TrainingWeek::withoutGlobalScopes()->where('training_plan_id', $plan->id)->count());
    }

    public function test_duplicate_uses_default_name_when_empty(): void
    {
        ['teacher' => $teacher, 'studentB' => $studentB, 'plan' => $plan] = $this->arrange();

        $this->actingAs($teacher->user);

        Livewire::test(PlanShow::class, ['plan' => $plan])
            ->call('openDuplicateForm')
            ->set('duplicateStudentId', $studentB->id)
            ->set('duplicateName', '')
            ->call('duplicatePlan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('training_plans', [
            'student_id' => $studentB->id,
            'name' => 'Periodização 12 semanas (cópia)',
            'status' => 'draft',
        ]);
    }

    public function test_duplicate_does_not_notify_the_target_student(): void
    {
        ['teacher' => $teacher, 'studentB' => $studentB, 'plan' => $plan] = $this->arrange();

        // O aluno B tem conta no app: a cópia (rascunho) não pode avisá-lo.
        Notification::fake();

        $this->actingAs($teacher->user);

        Livewire::test(PlanShow::class, ['plan' => $plan])
            ->call('openDuplicateForm')
            ->set('duplicateStudentId', $studentB->id)
            ->call('duplicatePlan')
            ->assertHasNoErrors();

        Notification::assertNotSentTo($studentB->user, NewTrainingAvailable::class);
    }

    public function test_cannot_duplicate_to_student_of_another_teacher(): void
    {
        ['teacher' => $teacher, 'studentB' => $studentB, 'plan' => $plan] = $this->arrange();
        $foreignStudent = Student::factory()->forTeacher(Teacher::factory()->create())->create();

        $this->actingAs($teacher->user);

        Livewire::test(PlanShow::class, ['plan' => $plan])
            ->call('openDuplicateForm')
            ->set('duplicateStudentId', $foreignStudent->id)
            ->call('duplicatePlan')
            ->assertHasErrors(['duplicateStudentId']);

        // Nada novo foi criado (só o plano original do aluno A).
        $this->assertSame(1, TrainingPlan::withoutGlobalScopes()->count());
        $this->assertSame(0, TrainingPlan::withoutGlobalScopes()->where('student_id', $foreignStudent->id)->count());
    }

    public function test_duplicate_requires_a_student(): void
    {
        ['teacher' => $teacher, 'studentB' => $studentB, 'plan' => $plan] = $this->arrange();

        $this->actingAs($teacher->user);

        Livewire::test(PlanShow::class, ['plan' => $plan])
            ->call('openDuplicateForm')
            ->set('duplicateStudentId', null)
            ->call('duplicatePlan')
            ->assertHasErrors(['duplicateStudentId']);

        $this->assertSame(1, TrainingPlan::withoutGlobalScopes()->count());
    }

    public function test_duplicate_week_appends_at_the_end_with_shifted_dates(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA, 'plan' => $plan] = $this->arrange();

        $week1 = TrainingWeek::withoutGlobalScopes()
            ->where('training_plan_id', $plan->id)->where('week_number', 1)->first();

        $this->actingAs($teacher->user);

        Livewire::test(PlanShow::class, ['plan' => $plan])
            ->call('duplicateWeek', $week1->id)
            ->assertHasNoErrors();

        // Nova semana no fim do plano, com o próximo Nº.
        $newWeek = TrainingWeek::withoutGlobalScopes()
            ->where('training_plan_id', $plan->id)->where('week_number', 3)->first();

        $this->assertNotNull($newWeek);
        $this->assertSame('Adaptação', $newWeek->name);

        // Datas deslocadas para logo após a última semana (12/10 → 26/10).
        $this->assertSame('2026-10-19', $newWeek->start_date->format('Y-m-d'));
        $this->assertSame('2026-10-25', $newWeek->end_date->format('Y-m-d'));

        // Sessões da semana 1 copiadas com as datas deslocadas +14 dias.
        $copied = TrainingSession::withoutGlobalScopes()
            ->where('training_week_id', $newWeek->id)->orderBy('id')->get();
        $this->assertCount(2, $copied);
        $this->assertSame($studentA->id, $copied->first()->student_id);
        $this->assertSame(TrainingSessionStatus::PLANNED, $copied->first()->status);

        $originalFirst = TrainingSession::withoutGlobalScopes()
            ->where('training_week_id', $week1->id)->orderBy('id')->first();
        $this->assertSame(
            $originalFirst->scheduled_date->copy()->addDays(14)->format('Y-m-d'),
            $copied->first()->scheduled_date->format('Y-m-d')
        );

        // Composição segue junto.
        $this->assertSame(2, TrainingSessionItem::where('training_session_id', $copied->first()->id)->count());
    }

    public function test_duplicate_week_blocks_week_of_another_tenant(): void
    {
        ['teacher' => $teacher, 'plan' => $plan] = $this->arrange();

        $foreignWeek = TrainingWeek::factory()
            ->forPlan(
                TrainingPlan::factory()
                    ->forStudent(Student::factory()->forTeacher(Teacher::factory()->create())->create())
                    ->create(),
                1
            )
            ->create();

        $this->actingAs($teacher->user);
        $this->withoutExceptionHandling();

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(PlanShow::class, ['plan' => $plan])
            ->call('duplicateWeek', $foreignWeek->id);
    }

    public function test_plan_show_renders_duplicate_controls(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA, 'studentB' => $studentB, 'plan' => $plan] = $this->arrange();

        $this->actingAs($teacher->user);

        Livewire::test(PlanShow::class, ['plan' => $plan])
            ->assertSee('Duplicar plano')
            ->assertDontSee('Aluno que vai receber a cópia')
            ->call('openDuplicateForm')
            ->assertSee('Aluno que vai receber a cópia')
            ->assertSee($studentA->name)
            ->assertSee($studentB->name)
            ->assertSet('duplicateStudentId', $plan->student_id)
            ->assertSet('duplicateName', $plan->name.' (cópia)')
            ->call('closeDuplicateForm')
            ->assertSet('showDuplicateForm', false)
            ->assertDontSee('Aluno que vai receber a cópia');
    }
}
