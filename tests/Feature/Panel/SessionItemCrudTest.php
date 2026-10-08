<?php

namespace Tests\Feature\Panel;

use App\Livewire\Dashboard\Plans\PlanShow;
use App\Livewire\Dashboard\Plans\SessionItems;
use App\Models\Exercise;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\TrainingSessionItem;
use App\Models\TrainingWeek;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SessionItemCrudTest extends TestCase
{
    use RefreshDatabase;

    private function createSessionFor(Teacher $teacher): TrainingSession
    {
        $student = Student::factory()->forTeacher($teacher)->create();
        $plan = TrainingPlan::factory()->forStudent($student)->create();
        $week = TrainingWeek::factory()->forPlan($plan, 1)->create();

        return TrainingSession::factory()->forWeek($week)->create();
    }

    public function test_teacher_adds_item_to_own_session(): void
    {
        $teacher = Teacher::factory()->create();
        $session = $this->createSessionFor($teacher);

        $this->actingAs($teacher->user);

        Livewire::test(SessionItems::class, ['session' => $session])
            ->call('openForm')
            ->set('item.type', 'warmup')
            ->set('item.title', 'Aquecimento dinâmico')
            ->set('item.duration', 600)
            ->set('item.intensity', 'Z1')
            ->call('saveItem')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('training_session_items', [
            'training_session_id' => $session->id,
            'type' => 'warmup',
            'title' => 'Aquecimento dinâmico',
            'duration' => 600,
            'sort_order' => 1,
        ]);
    }

    public function test_item_requires_type_and_title(): void
    {
        $teacher = Teacher::factory()->create();
        $session = $this->createSessionFor($teacher);

        $this->actingAs($teacher->user);

        Livewire::test(SessionItems::class, ['session' => $session])
            ->call('openForm')
            ->call('saveItem')
            ->assertHasErrors(['item.type', 'item.title']);

        $this->assertSame(0, TrainingSessionItem::withoutGlobalScopes()->count());
    }

    public function test_teacher_updates_own_item(): void
    {
        $teacher = Teacher::factory()->create();
        $session = $this->createSessionFor($teacher);
        $item = TrainingSessionItem::factory()->forSession($session)->create([
            'title' => 'Título antigo',
        ]);

        $this->actingAs($teacher->user);

        Livewire::test(SessionItems::class, ['session' => $session])
            ->call('editItem', $item->id)
            ->set('item.title', 'Título novo')
            ->set('item.repetitions', 6)
            ->call('saveItem')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('training_session_items', [
            'id' => $item->id,
            'title' => 'Título novo',
            'repetitions' => 6,
        ]);
    }

    public function test_teacher_deletes_item_and_renumbers_sequence(): void
    {
        $teacher = Teacher::factory()->create();
        $session = $this->createSessionFor($teacher);
        $first = TrainingSessionItem::factory()->forSession($session, 1)->create(['title' => 'Primeiro']);
        $second = TrainingSessionItem::factory()->forSession($session, 2)->create(['title' => 'Segundo']);

        $this->actingAs($teacher->user);

        Livewire::test(SessionItems::class, ['session' => $session])
            ->call('deleteItem', $first->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('training_session_items', ['id' => $first->id]);
        $this->assertDatabaseHas('training_session_items', [
            'id' => $second->id,
            'sort_order' => 1,
        ]);
    }

    public function test_teacher_moves_item_down(): void
    {
        $teacher = Teacher::factory()->create();
        $session = $this->createSessionFor($teacher);
        $first = TrainingSessionItem::factory()->forSession($session, 1)->create(['title' => 'Primeiro']);
        $second = TrainingSessionItem::factory()->forSession($session, 2)->create(['title' => 'Segundo']);

        $this->actingAs($teacher->user);

        Livewire::test(SessionItems::class, ['session' => $session])
            ->call('moveItem', $first->id, 1)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('training_session_items', ['id' => $first->id, 'sort_order' => 2]);
        $this->assertDatabaseHas('training_session_items', ['id' => $second->id, 'sort_order' => 1]);
    }

    public function test_cannot_link_item_to_exercise_of_another_tenant(): void
    {
        $teacherA = Teacher::factory()->create();
        $sessionA = $this->createSessionFor($teacherA);
        $exerciseB = Exercise::factory()->forTeacher(Teacher::factory()->create())->create();

        $this->actingAs($teacherA->user);

        Livewire::test(SessionItems::class, ['session' => $sessionA])
            ->call('openForm')
            ->set('item.type', 'work')
            ->set('item.title', 'Item com exercício alheio')
            ->set('item.exercise_id', $exerciseB->id)
            ->call('saveItem')
            ->assertHasErrors(['item.exercise_id']);

        $this->assertSame(0, TrainingSessionItem::withoutGlobalScopes()->count());
    }

    public function test_items_component_denies_direct_model_of_another_tenant(): void
    {
        $teacherA = Teacher::factory()->create();
        $sessionB = $this->createSessionFor(Teacher::factory()->create());

        $this->actingAs($teacherA->user);
        $this->withoutExceptionHandling();

        $this->expectException(AuthorizationException::class);

        Livewire::test(SessionItems::class, ['session' => $sessionB]);
    }

    public function test_plan_show_toggles_items_panel(): void
    {
        $teacher = Teacher::factory()->create();
        $session = $this->createSessionFor($teacher);

        $this->actingAs($teacher->user);

        Livewire::test(PlanShow::class, ['plan' => $session->week->plan])
            ->call('toggleItems', $session->id)
            ->assertSee('Composição do treino');
    }

    public function test_global_exercise_can_be_linked_to_item(): void
    {
        $teacher = Teacher::factory()->create();
        $session = $this->createSessionFor($teacher);
        $globalExercise = Exercise::factory()->create(); // teacher_id = null (catálogo global)

        $this->actingAs($teacher->user);

        Livewire::test(SessionItems::class, ['session' => $session])
            ->call('openForm')
            ->set('item.type', 'work')
            ->set('item.title', 'Pedalada em Z2')
            ->set('item.exercise_id', $globalExercise->id)
            ->call('saveItem')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('training_session_items', [
            'training_session_id' => $session->id,
            'exercise_id' => $globalExercise->id,
        ]);
    }
}
