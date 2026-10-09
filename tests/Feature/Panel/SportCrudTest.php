<?php

namespace Tests\Feature\Panel;

use App\Livewire\Dashboard\Sports\SportForm;
use App\Livewire\Dashboard\Sports\SportIndex;
use App\Models\Exercise;
use App\Models\Sport;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TrainingPlan;
use App\Models\TrainingWeek;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SportCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_sport(): void
    {
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        Livewire::test(SportForm::class)
            ->set('name', 'Duatlo')
            ->set('description', 'Corrida + Bicicleta')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('sports.index'));

        $this->assertDatabaseHas('sports', [
            'name' => 'Duatlo',
            'slug' => 'duatlo',
            'active' => true,
        ]);
    }

    public function test_sport_requires_name(): void
    {
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        Livewire::test(SportForm::class)
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name']);

        $this->assertSame(0, Sport::count());
    }

    public function test_duplicate_sport_name_fails(): void
    {
        Sport::factory()->create(['name' => 'Corrida']);
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        Livewire::test(SportForm::class)
            ->set('name', 'Corrida')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_admin_updates_sport(): void
    {
        $sport = Sport::factory()->create(['name' => 'Ciclismo']);
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        Livewire::test(SportForm::class, ['sport' => $sport])
            ->set('name', 'Ciclismo de Estrada')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sports', [
            'id' => $sport->id,
            'name' => 'Ciclismo de Estrada',
            'slug' => 'ciclismo-de-estrada',
        ]);
    }

    public function test_admin_deletes_unused_sport(): void
    {
        $sport = Sport::factory()->create();
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        Livewire::test(SportIndex::class)
            ->call('confirmDelete', $sport->id)
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('sports', ['id' => $sport->id]);
    }

    public function test_sport_in_use_cannot_be_deleted(): void
    {
        $sport = Sport::factory()->create();
        $exercise = Exercise::factory()->create(['sport_id' => $sport->id]);
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        Livewire::test(SportIndex::class)
            ->call('confirmDelete', $sport->id)
            ->call('delete');

        $this->assertDatabaseHas('sports', ['id' => $sport->id]);
        $this->assertDatabaseHas('exercises', ['id' => $exercise->id]);
    }

    public function test_teacher_is_blocked_from_sports_routes(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        $this->get(route('sports.index'))->assertForbidden();
        $this->get(route('sports.create'))->assertForbidden();
    }

    public function test_teacher_cannot_manage_sport_via_component(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);
        $this->withoutExceptionHandling();

        $this->expectException(AuthorizationException::class);

        Livewire::test(SportForm::class);
    }

    public function test_sport_routes_render_for_admin(): void
    {
        $sport = Sport::factory()->create();
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        $this->get(route('sports.index'))->assertOk();
        $this->get(route('sports.create'))->assertOk();
        $this->get(route('sports.edit', $sport))->assertOk();
    }

    public function test_teacher_still_renders_session_form_with_sports_dropdown(): void
    {
        // O treinador consulta o catálogo (dropdown) mesmo sem gerenciá-lo.
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $plan = TrainingPlan::factory()->forStudent($student)->create();
        TrainingWeek::factory()->forPlan($plan, 1)->create();
        Sport::factory()->create(['name' => 'Natação']);

        $this->actingAs($teacher->user);

        $this->get(route('plans.show', $plan))->assertOk();
    }
}
