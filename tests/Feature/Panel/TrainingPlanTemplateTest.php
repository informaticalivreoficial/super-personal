<?php

namespace Tests\Feature\Panel;

use App\Enums\TrainingPlanStatus;
use App\Enums\TrainingSessionStatus;
use App\Enums\TrainingWeekStatus;
use App\Enums\UserRole;
use App\Livewire\Dashboard\Plans\PlanShow;
use App\Livewire\Dashboard\TrainingTemplates\TemplateIndex;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\TrainingSessionItem;
use App\Models\TrainingWeek;
use App\Notifications\NewTrainingAvailable;
use App\Services\TrainingPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Modelos de treino (templates): salvar um plano como periodização reaplicável
 * e aplicar a outro aluno recalculando as datas.
 */
class TrainingPlanTemplateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{teacher: Teacher, studentA: Student, studentB: Student, sport: Sport, plan: TrainingPlan}
     */
    private function arrange(): array
    {
        $teacher = Teacher::factory()->create();
        $studentA = Student::factory()->forTeacher($teacher)->create(['name' => 'Ana Aluna']);
        $studentB = Student::factory()->forTeacher($teacher)->create(['name' => 'Bruno Aluno']);
        $sport = Sport::factory()->create(['name' => 'Corrida']);

        $plan = TrainingPlan::factory()->forStudent($studentA)->create([
            'name' => 'Base 2 semanas',
            'description' => 'Periodização de base',
            'goal' => '5 km',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-18',
            'status' => TrainingPlanStatus::ACTIVE,
        ]);

        $week1 = TrainingWeek::factory()->forPlan($plan, 1)->create([
            'name' => 'Adaptação',
            'objective' => 'Criar base',
            'notes' => 'Semana leve',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-11',
            'status' => TrainingWeekStatus::COMPLETED,
        ]);
        $week2 = TrainingWeek::factory()->forPlan($plan, 2)->create([
            'name' => 'Progressão',
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-18',
        ]);

        $session1 = TrainingSession::factory()->forWeek($week1, ['sport_id' => $sport->id])->create([
            'title' => 'Rodagem',
            'scheduled_date' => '2026-10-05',
            'status' => TrainingSessionStatus::COMPLETED,
            'estimated_duration' => 3600,
            'distance' => 8000,
            'intensity' => 'Z2',
            'target_pace' => '6:00/km',
            'sort_order' => 1,
        ]);
        TrainingSession::factory()->forWeek($week1, ['sport_id' => $sport->id])->create([
            'title' => 'Intervalado',
            'scheduled_date' => '2026-10-07',
            'status' => TrainingSessionStatus::COMPLETED,
            'sort_order' => 2,
        ]);
        TrainingSession::factory()->forWeek($week2, ['sport_id' => $sport->id])->create([
            'title' => 'Longão',
            'scheduled_date' => '2026-10-17',
            'sort_order' => 1,
        ]);

        TrainingSessionItem::factory()->forSession($session1, 1)->create([
            'type' => 'warmup',
            'title' => 'Aquecimento',
        ]);
        TrainingSessionItem::factory()->forSession($session1, 2)->create([
            'type' => 'work',
            'title' => 'Bloco principal',
            'sets' => 4,
            'repetitions' => 1,
        ]);

        return compact('teacher', 'studentA', 'studentB', 'sport', 'plan');
    }

    public function test_saves_plan_as_template_with_relative_offsets(): void
    {
        ['teacher' => $teacher, 'sport' => $sport, 'plan' => $plan] = $this->arrange();

        $this->actingAs($teacher->user);

        $template = app(TrainingPlanService::class)->saveAsTemplate($plan, [
            'name' => 'Base reutilizável',
            'description' => 'Modelo de 2 semanas',
        ]);

        $this->assertSame($teacher->id, $template->teacher_id);
        $this->assertSame('Base reutilizável', $template->name);
        $this->assertSame('Modelo de 2 semanas', $template->description);
        $this->assertSame('5 km', $template->goal);
        $this->assertSame($sport->id, $template->sport_id);
        $this->assertSame(2, $template->weeks_count);
        $this->assertSame(3, $template->sessions_count);

        $structure = $template->structure;
        $this->assertCount(2, $structure['weeks']);
        $this->assertSame(0, $structure['weeks'][0]['start_offset_days']);
        $this->assertSame(6, $structure['weeks'][0]['end_offset_days']);
        $this->assertSame(7, $structure['weeks'][1]['start_offset_days']);
        $this->assertSame(13, $structure['weeks'][1]['end_offset_days']);

        $firstSession = $structure['weeks'][0]['sessions'][0];
        $this->assertSame('Rodagem', $firstSession['title']);
        $this->assertSame(0, $firstSession['scheduled_offset_days']);
        $this->assertSame(3600, $firstSession['estimated_duration']);
        $this->assertSame('Z2', $firstSession['intensity']);
        $this->assertCount(2, $firstSession['items']);
        $this->assertSame('warmup', $firstSession['items'][0]['type']);
        $this->assertSame(4, $firstSession['items'][1]['sets']);
    }

    public function test_apply_template_creates_a_draft_plan_with_recalculated_dates(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA, 'studentB' => $studentB, 'plan' => $plan] = $this->arrange();

        $this->actingAs($teacher->user);

        $service = app(TrainingPlanService::class);
        $template = $service->saveAsTemplate($plan, ['name' => 'Base reutilizável']);
        $copy = $service->applyTemplate($template, $studentB, '2026-11-02', 'Novo ciclo');

        $this->assertSame($studentB->id, $copy->student_id);
        $this->assertSame($teacher->id, $copy->teacher_id);
        $this->assertSame('Novo ciclo', $copy->name);
        $this->assertSame(TrainingPlanStatus::DRAFT, $copy->status);
        $this->assertSame('2026-11-02', $copy->start_date->format('Y-m-d'));
        $this->assertSame('2026-11-15', $copy->end_date->format('Y-m-d'));

        $weeks = TrainingWeek::withoutGlobalScopes()->where('training_plan_id', $copy->id)
            ->orderBy('week_number')->get();
        $this->assertCount(2, $weeks);
        $this->assertSame('2026-11-02', $weeks[0]->start_date->format('Y-m-d'));
        $this->assertSame('2026-11-08', $weeks[0]->end_date->format('Y-m-d'));
        $this->assertSame('2026-11-09', $weeks[1]->start_date->format('Y-m-d'));
        $this->assertSame(TrainingWeekStatus::PENDING, $weeks[0]->status);

        $sessions = TrainingSession::withoutGlobalScopes()->where('student_id', $studentB->id)
            ->orderBy('scheduled_date')->get();
        $this->assertCount(3, $sessions);
        $this->assertSame('2026-11-02', $sessions[0]->scheduled_date->format('Y-m-d'));
        $this->assertSame('2026-11-04', $sessions[1]->scheduled_date->format('Y-m-d'));
        $this->assertSame('2026-11-14', $sessions[2]->scheduled_date->format('Y-m-d'));
        $this->assertSame(TrainingSessionStatus::PLANNED, $sessions[0]->status);

        // Composição copiada para a sessão certa.
        $this->assertSame(2, TrainingSessionItem::where('training_session_id', $sessions[0]->id)->count());
        $this->assertSame(0, TrainingSessionItem::where('training_session_id', $sessions[1]->id)->count());

        // Origem intacta.
        $this->assertSame(TrainingPlanStatus::ACTIVE, $plan->fresh()->status);
        $this->assertSame(3, TrainingSession::withoutGlobalScopes()->where('student_id', $studentA->id)->count());
    }

    public function test_applied_plan_starts_as_draft_and_does_not_notify_the_student(): void
    {
        ['teacher' => $teacher, 'studentB' => $studentB, 'plan' => $plan] = $this->arrange();

        $this->actingAs($teacher->user);
        Notification::fake();

        $service = app(TrainingPlanService::class);
        $template = $service->saveAsTemplate($plan, ['name' => 'Base']);
        $copy = $service->applyTemplate($template, $studentB, '2026-11-02');

        $this->assertSame(TrainingPlanStatus::DRAFT, $copy->status);
        Notification::assertNotSentTo($studentB->user, NewTrainingAvailable::class);
    }

    public function test_plan_show_saves_the_plan_as_a_template(): void
    {
        ['teacher' => $teacher, 'plan' => $plan] = $this->arrange();

        $this->actingAs($teacher->user);

        Livewire::test(PlanShow::class, ['plan' => $plan])
            ->call('openTemplateForm')
            ->assertSet('showTemplateForm', true)
            ->assertSet('templateName', 'Base 2 semanas (modelo)')
            ->set('templateName', 'Modelo personalizado')
            ->set('templateDescription', 'Reaproveitar na próxima temporada')
            ->call('saveAsTemplate')
            ->assertHasNoErrors()
            ->assertSet('showTemplateForm', false)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('training_plan_templates', [
            'teacher_id' => $teacher->id,
            'name' => 'Modelo personalizado',
            'description' => 'Reaproveitar na próxima temporada',
            'weeks_count' => 2,
            'sessions_count' => 3,
        ]);
    }

    public function test_template_name_is_required_when_saving(): void
    {
        ['teacher' => $teacher, 'plan' => $plan] = $this->arrange();

        $this->actingAs($teacher->user);

        Livewire::test(PlanShow::class, ['plan' => $plan])
            ->call('openTemplateForm')
            ->set('templateName', '')
            ->call('saveAsTemplate')
            ->assertHasErrors(['templateName' => 'required']);

        $this->assertDatabaseCount('training_plan_templates', 0);
    }

    public function test_index_lists_only_templates_of_the_authenticated_teacher(): void
    {
        ['teacher' => $teacher, 'plan' => $plan] = $this->arrange();
        $otherTeacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);
        app(TrainingPlanService::class)->saveAsTemplate($plan, ['name' => 'Modelo do professor A']);

        $this->actingAs($otherTeacher->user);
        Livewire::test(TemplateIndex::class)
            ->assertDontSee('Modelo do professor A')
            ->assertSee('Nenhum modelo salvo ainda');
    }

    public function test_index_applies_a_template_to_a_student(): void
    {
        ['teacher' => $teacher, 'studentB' => $studentB, 'plan' => $plan] = $this->arrange();

        $this->actingAs($teacher->user);
        $template = app(TrainingPlanService::class)->saveAsTemplate($plan, ['name' => 'Base']);

        Livewire::test(TemplateIndex::class)
            ->assertSee('Base')
            ->call('openApply', $template->id)
            ->assertSet('applyTemplateId', $template->id)
            ->assertSet('applyName', 'Base')
            ->set('applyStudentId', $studentB->id)
            ->set('applyStartDate', '2026-11-02')
            ->set('applyName', 'Ciclo de novembro')
            ->call('apply')
            ->assertHasNoErrors()
            ->assertRedirect();

        $copy = TrainingPlan::withoutGlobalScopes()->where('student_id', $studentB->id)->first();
        $this->assertNotNull($copy);
        $this->assertSame('Ciclo de novembro', $copy->name);
        $this->assertSame('2026-11-02', $copy->start_date->format('Y-m-d'));
    }

    public function test_template_cannot_be_applied_to_a_student_from_another_teacher(): void
    {
        ['teacher' => $teacher, 'plan' => $plan] = $this->arrange();
        $otherTeacher = Teacher::factory()->create();
        $foreignStudent = Student::factory()->forTeacher($otherTeacher)->create();

        $this->actingAs($teacher->user);
        $template = app(TrainingPlanService::class)->saveAsTemplate($plan, ['name' => 'Base']);

        Livewire::test(TemplateIndex::class)
            ->call('openApply', $template->id)
            ->set('applyStudentId', $foreignStudent->id)
            ->set('applyStartDate', '2026-11-02')
            ->call('apply')
            ->assertHasErrors(['applyStudentId']);

        $this->assertSame(0, TrainingPlan::withoutGlobalScopes()
            ->where('student_id', $foreignStudent->id)->count());
    }

    public function test_delete_removes_only_the_tenant_template(): void
    {
        ['teacher' => $teacher, 'plan' => $plan] = $this->arrange();
        $otherTeacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);
        $template = app(TrainingPlanService::class)->saveAsTemplate($plan, ['name' => 'Base']);

        Livewire::test(TemplateIndex::class)
            ->call('delete', $template->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('training_plan_templates', ['id' => $template->id]);

        // Outro treinador não alcança o modelo (escopo do tenant → 404).
        $this->actingAs($otherTeacher->user);

        Livewire::test(TemplateIndex::class)
            ->call('delete', $template->id)
            ->assertStatus(404);
    }

    public function test_guest_is_redirected_and_admin_is_forbidden(): void
    {
        $this->get(route('templates.index'))->assertRedirect(route('login'));

        $admin = Teacher::factory()->create()->user;
        $admin->forceFill(['role' => UserRole::ADMIN])->save();
        $admin->refresh();

        $this->actingAs($admin)->get(route('templates.index'))->assertForbidden();
    }
}
