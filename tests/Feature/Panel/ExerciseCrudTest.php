<?php

namespace Tests\Feature\Panel;

use App\Livewire\Dashboard\Exercises\ExerciseForm;
use App\Livewire\Dashboard\Exercises\ExerciseIndex;
use App\Models\Exercise;
use App\Models\Sport;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ExerciseCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_creates_own_exercise(): void
    {
        $teacher = Teacher::factory()->create();
        $sport = Sport::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(ExerciseForm::class)
            ->set('sport_id', $sport->id)
            ->set('name', 'Prancha lateral 45s')
            ->set('difficulty', 'medium')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('exercises.index'));

        $this->assertDatabaseHas('exercises', [
            'teacher_id' => $teacher->id,
            'sport_id' => $sport->id,
            'name' => 'Prancha lateral 45s',
            'difficulty' => 'medium',
            'slug' => 'prancha-lateral-45s',
        ]);
    }

    public function test_exercise_requires_name_and_sport(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(ExerciseForm::class)
            ->set('sport_id', '')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['sport_id', 'name']);

        $this->assertSame(0, Exercise::withoutGlobalScopes()->count());
    }

    public function test_cannot_create_exercise_with_duplicated_name_in_own_library(): void
    {
        $teacher = Teacher::factory()->create();
        $sport = Sport::factory()->create();
        Exercise::factory()->forTeacher($teacher)->create(['name' => 'Agachamento livre']);

        $this->actingAs($teacher->user);

        Livewire::test(ExerciseForm::class)
            ->set('sport_id', $sport->id)
            ->set('name', 'Agachamento livre')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_same_name_allowed_for_different_teachers(): void
    {
        $teacherA = Teacher::factory()->create();
        $sport = Sport::factory()->create();
        Exercise::factory()->forTeacher(Teacher::factory()->create())->create(['name' => 'Agachamento livre']);

        $this->actingAs($teacherA->user);

        Livewire::test(ExerciseForm::class)
            ->set('sport_id', $sport->id)
            ->set('name', 'Agachamento livre')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('exercises', [
            'teacher_id' => $teacherA->id,
            'name' => 'Agachamento livre',
        ]);
    }

    public function test_teacher_updates_own_exercise(): void
    {
        $teacher = Teacher::factory()->create();
        $exercise = Exercise::factory()->forTeacher($teacher)->create(['name' => 'Nome antigo']);

        $this->actingAs($teacher->user);

        Livewire::test(ExerciseForm::class, ['exercise' => $exercise->id])
            ->set('name', 'Nome novo')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('exercises', [
            'id' => $exercise->id,
            'teacher_id' => $teacher->id, // posse preservada
            'name' => 'Nome novo',
        ]);
    }

    public function test_exercise_of_another_tenant_returns_404_on_edit_route(): void
    {
        $teacherA = Teacher::factory()->create();
        $exerciseB = Exercise::factory()->forTeacher(Teacher::factory()->create())->create();

        $this->actingAs($teacherA->user);

        $this->get(route('exercises.edit', $exerciseB))->assertNotFound();
    }

    public function test_global_exercise_is_read_only_for_teacher(): void
    {
        $teacher = Teacher::factory()->create();
        $global = Exercise::factory()->create(); // teacher_id = null

        $this->actingAs($teacher->user);

        $this->get(route('exercises.edit', $global))->assertForbidden();
    }

    public function test_teacher_deletes_own_exercise_logically(): void
    {
        $teacher = Teacher::factory()->create();
        $exercise = Exercise::factory()->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        Livewire::test(ExerciseIndex::class)
            ->call('confirmDelete', $exercise->id)
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertSoftDeleted('exercises', ['id' => $exercise->id]);
    }

    public function test_teacher_cannot_delete_exercise_of_another_tenant(): void
    {
        $teacherA = Teacher::factory()->create();
        $exerciseB = Exercise::factory()->forTeacher(Teacher::factory()->create())->create();

        $this->actingAs($teacherA->user);
        $this->withoutExceptionHandling();

        $this->expectException(NotFoundHttpException::class);

        Livewire::test(ExerciseIndex::class)->call('confirmDelete', $exerciseB->id);
    }

    public function test_teacher_lists_only_own_and_global_exercises(): void
    {
        $teacherA = Teacher::factory()->create();
        $teacherB = Teacher::factory()->create();
        Exercise::factory()->forTeacher($teacherA)->create(['name' => 'Exercício de A']);
        Exercise::factory()->forTeacher($teacherB)->create(['name' => 'Exercício de B']);
        Exercise::factory()->create(['name' => 'Exercício Global']);

        $this->actingAs($teacherA->user);

        Livewire::test(ExerciseIndex::class)
            ->assertSee('Exercício de A')
            ->assertSee('Exercício Global')
            ->assertDontSee('Exercício de B');
    }

    public function test_admin_edits_teacher_exercise_without_changing_owner(): void
    {
        $teacher = Teacher::factory()->create();
        $exercise = Exercise::factory()->forTeacher($teacher)->create(['name' => 'Antes']);
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        $this->get(route('exercises.edit', $exercise))->assertOk();

        Livewire::test(ExerciseForm::class, ['exercise' => $exercise->id])
            ->set('name', 'Depois')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('exercises', [
            'id' => $exercise->id,
            'teacher_id' => $teacher->id,
            'name' => 'Depois',
        ]);
    }

    public function test_exercise_routes_render_for_teacher(): void
    {
        $teacher = Teacher::factory()->create();
        $exercise = Exercise::factory()->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        $this->get(route('exercises.index'))->assertOk();
        $this->get(route('exercises.create'))->assertOk();
        $this->get(route('exercises.edit', $exercise))->assertOk();
    }
}
