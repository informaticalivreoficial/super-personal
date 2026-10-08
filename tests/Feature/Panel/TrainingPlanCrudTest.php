<?php

namespace Tests\Feature\Panel;

use App\Livewire\Dashboard\Plans\PlanForm;
use App\Livewire\Dashboard\Plans\PlanIndex;
use App\Livewire\Dashboard\Plans\PlanShow;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\TrainingWeek;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrainingPlanCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_lists_only_own_plans(): void
    {
        $teacherA = Teacher::factory()->create();
        $teacherB = Teacher::factory()->create();
        $studentA = Student::factory()->forTeacher($teacherA)->create();
        $studentB = Student::factory()->forTeacher($teacherB)->create();
        TrainingPlan::factory()->forStudent($studentA)->create(['name' => 'Plano de A']);
        TrainingPlan::factory()->forStudent($studentB)->create(['name' => 'Plano de B']);

        $this->actingAs($teacherA->user);

        Livewire::test(PlanIndex::class)
            ->assertSee('Plano de A')
            ->assertDontSee('Plano de B');
    }

    public function test_teacher_creates_plan_for_own_student(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        Livewire::test(PlanForm::class)
            ->set('student_id', $student->id)
            ->set('name', 'Preparação 10km')
            ->set('start_date', '2026-10-10')
            ->set('end_date', '2026-12-10')
            ->set('status', 'active')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('training_plans', [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'name' => 'Preparação 10km',
            'status' => 'active',
        ]);
    }

    public function test_cannot_create_plan_for_student_of_another_teacher(): void
    {
        $teacherA = Teacher::factory()->create();
        $studentB = Student::factory()->forTeacher(Teacher::factory()->create())->create();

        $this->actingAs($teacherA->user);

        Livewire::test(PlanForm::class)
            ->set('student_id', $studentB->id)
            ->set('name', 'Plano inválido')
            ->set('start_date', '2026-10-10')
            ->call('save')
            ->assertHasErrors(['student_id']);

        $this->assertDatabaseMissing('training_plans', ['name' => 'Plano inválido']);
    }

    public function test_teacher_updates_own_plan(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $plan = TrainingPlan::factory()->forStudent($student)->create(['name' => 'Nome antigo']);

        $this->actingAs($teacher->user);

        Livewire::test(PlanForm::class, ['plan' => $plan])
            ->set('name', 'Nome novo')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('training_plans', ['id' => $plan->id, 'name' => 'Nome novo']);
    }

    public function test_plan_routes_return_404_for_another_tenant(): void
    {
        $teacherA = Teacher::factory()->create();
        $planB = TrainingPlan::factory()
            ->forStudent(Student::factory()->forTeacher(Teacher::factory()->create())->create())
            ->create();

        $this->actingAs($teacherA->user);

        $this->get(route('plans.edit', $planB))->assertNotFound();
        $this->get(route('plans.show', $planB))->assertNotFound();
    }

    public function test_teacher_deletes_own_plan(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $plan = TrainingPlan::factory()->forStudent($student)->create();

        $this->actingAs($teacher->user);

        Livewire::test(PlanIndex::class)
            ->call('confirmDelete', $plan->id)
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('training_plans', ['id' => $plan->id]);
    }

    public function test_teacher_creates_week_on_plan_show(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $plan = TrainingPlan::factory()->forStudent($student)->create([
            'start_date' => '2026-10-10',
            'end_date' => '2026-12-10',
        ]);

        $this->actingAs($teacher->user);

        Livewire::test(PlanShow::class, ['plan' => $plan])
            ->call('openWeekForm')
            ->set('week.week_number', 1)
            ->set('week.name', 'Semana 1')
            ->set('week.start_date', '2026-10-10')
            ->set('week.end_date', '2026-10-16')
            ->call('saveWeek')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('training_weeks', [
            'training_plan_id' => $plan->id,
            'week_number' => 1,
            'name' => 'Semana 1',
        ]);
    }

    public function test_duplicate_week_number_fails(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $plan = TrainingPlan::factory()->forStudent($student)->create();
        TrainingWeek::factory()->forPlan($plan, 1)->create([
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-16',
        ]);

        $this->actingAs($teacher->user);

        Livewire::test(PlanShow::class, ['plan' => $plan])
            ->call('openWeekForm')
            ->set('week.week_number', 1)
            ->set('week.start_date', '2026-10-17')
            ->set('week.end_date', '2026-10-23')
            ->call('saveWeek')
            ->assertHasErrors(['week.week_number']);

        $this->assertSame(1, TrainingWeek::withoutGlobalScopes()->count());
    }

    public function test_teacher_creates_session_on_week(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $plan = TrainingPlan::factory()->forStudent($student)->create();
        $week = TrainingWeek::factory()->forPlan($plan, 1)->create([
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-16',
        ]);
        $sport = Sport::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(PlanShow::class, ['plan' => $plan])
            ->call('openSessionForm', $week->id)
            ->set('session.sport_id', $sport->id)
            ->set('session.title', 'Corrida contínua')
            ->set('session.scheduled_date', '2026-10-12')
            ->set('session.estimated_duration', 45)
            ->set('session.distance', 8000)
            ->call('saveSession')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('training_sessions', [
            'training_week_id' => $week->id,
            'student_id' => $student->id,
            'title' => 'Corrida contínua',
            'sport_id' => $sport->id,
        ]);
    }

    public function test_session_requires_sport(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $plan = TrainingPlan::factory()->forStudent($student)->create();
        $week = TrainingWeek::factory()->forPlan($plan, 1)->create([
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-16',
        ]);

        $this->actingAs($teacher->user);

        Livewire::test(PlanShow::class, ['plan' => $plan])
            ->call('openSessionForm', $week->id)
            ->set('session.title', 'Sem modalidade')
            ->set('session.scheduled_date', '2026-10-12')
            ->call('saveSession')
            ->assertHasErrors(['session.sport_id']);

        $this->assertSame(0, TrainingSession::withoutGlobalScopes()->count());
    }

    public function test_teacher_deletes_session(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $plan = TrainingPlan::factory()->forStudent($student)->create();
        $week = TrainingWeek::factory()->forPlan($plan, 1)->create();
        $session = TrainingSession::factory()->forWeek($week)->create();

        $this->actingAs($teacher->user);

        Livewire::test(PlanShow::class, ['plan' => $plan])
            ->call('deleteSession', $session->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('training_sessions', ['id' => $session->id]);
    }

    public function test_teacher_cannot_manage_plan_of_another_tenant(): void
    {
        $teacherA = Teacher::factory()->create();
        $planB = TrainingPlan::factory()
            ->forStudent(Student::factory()->forTeacher(Teacher::factory()->create())->create())
            ->create();

        $this->actingAs($teacherA->user);
        $this->withoutExceptionHandling();

        $this->expectException(AuthorizationException::class);

        Livewire::test(PlanShow::class, ['plan' => $planB]);
    }

    public function test_plan_routes_render_for_teacher(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $plan = TrainingPlan::factory()->forStudent($student)->create();

        $this->actingAs($teacher->user);

        $this->get(route('plans.index'))->assertOk();
        $this->get(route('plans.create'))->assertOk();
        $this->get(route('plans.edit', $plan))->assertOk();
        $this->get(route('plans.show', $plan))->assertOk();
    }
}
