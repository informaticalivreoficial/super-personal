<?php

namespace Tests\Feature\Panel;

use App\Livewire\Dashboard\Professores\ProfessorForm;
use App\Livewire\Dashboard\Professores\ProfessorIndex;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\TeacherService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfessorCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_views_professors_index(): void
    {
        $admin = User::factory(['role' => 'admin'])->create();
        $teacher = Teacher::factory()->create();
        Student::factory()->forTeacher($teacher)->create();

        $this->actingAs($admin);

        $this->get(route('professors.index'))
            ->assertOk()
            ->assertSee('Treinadores')
            ->assertSee($teacher->name)
            ->assertSee($teacher->user->email);
    }

    public function test_teacher_is_blocked_from_professors_routes(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        $this->get(route('professors.index'))->assertForbidden();
        $this->get(route('professors.create'))->assertForbidden();
        $this->get(route('professors.edit', $teacher))->assertForbidden();
    }

    public function test_guest_is_redirected_from_professors_index(): void
    {
        $this->get(route('professors.index'))->assertRedirect('/auth/login');
    }

    public function test_platform_admin_creates_professor_with_tenant_profile(): void
    {
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        Livewire::test(ProfessorForm::class)
            ->set('name', 'Maria Treinadora')
            ->set('email', 'maria@exemplo.com')
            ->set('phone', '(11) 99999-0000')
            ->set('specialty', 'Triathlon')
            ->set('password', 'senha12345')
            ->set('password_confirmation', 'senha12345')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('professors.index'));

        $user = User::where('email', 'maria@exemplo.com')->firstOrFail();

        $this->assertSame('teacher', $user->role->value);
        $this->assertSame(1, $user->status);

        // Tenant nascido: perfil em teachers vinculado ao user.
        $this->assertDatabaseHas('teachers', [
            'user_id' => $user->id,
            'name' => 'Maria Treinadora',
            'phone' => '(11) 99999-0000',
            'specialty' => 'Triathlon',
            'active' => true,
        ]);
    }

    public function test_professor_form_validates_required_fields(): void
    {
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        Livewire::test(ProfessorForm::class)
            ->set('name', '')
            ->set('email', '')
            ->set('password', '')
            ->set('password_confirmation', '')
            ->call('save')
            ->assertHasErrors(['name', 'email', 'password']);

        $this->assertSame(0, Teacher::count());
    }

    public function test_professor_email_must_be_unique(): void
    {
        Teacher::factory()->create();
        $taken = User::where('role', 'teacher')->first()->email;

        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        Livewire::test(ProfessorForm::class)
            ->set('name', 'Outro Treinador')
            ->set('email', $taken)
            ->set('password', 'senha12345')
            ->set('password_confirmation', 'senha12345')
            ->call('save')
            ->assertHasErrors(['email']);

        $this->assertSame(1, Teacher::count());
    }

    public function test_platform_admin_updates_professor(): void
    {
        $teacher = Teacher::factory()->create(['name' => 'Nome Antigo', 'specialty' => 'Corrida']);
        $ownEmail = $teacher->user->email;
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        // E-mail próprio não pode cair no "unique" (ignore do update).
        Livewire::test(ProfessorForm::class, ['teacher' => $teacher])
            ->set('name', 'Nome Novo')
            ->set('email', $ownEmail)
            ->set('specialty', 'Natação')
            ->set('password', '')
            ->set('password_confirmation', '')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('professors.index'));

        $teacher->refresh();
        $this->assertSame('Nome Novo', $teacher->name);
        $this->assertSame('Natação', $teacher->specialty);
        $this->assertSame($ownEmail, $teacher->user->email);

        // Senha em branco mantém a atual.
        $this->assertTrue(Hash::check('password', $teacher->user->password));

        // Senha nova troca o hash.
        Livewire::test(ProfessorForm::class, ['teacher' => $teacher])
            ->set('name', 'Nome Novo')
            ->set('email', $ownEmail)
            ->set('password', 'senhaNova123')
            ->set('password_confirmation', 'senhaNova123')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('senhaNova123', $teacher->user->fresh()->password));
    }

    public function test_toggle_active_blocks_login_and_profile(): void
    {
        $teacher = Teacher::factory()->create();
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        Livewire::test(ProfessorIndex::class)
            ->call('toggleActive', $teacher->id);

        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'active' => false]);
        $this->assertDatabaseHas('users', ['id' => $teacher->user_id, 'status' => 0]);

        // Ativa de volta.
        Livewire::test(ProfessorIndex::class)
            ->call('toggleActive', $teacher->id);

        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'active' => true]);
        $this->assertDatabaseHas('users', ['id' => $teacher->user_id, 'status' => 1]);
    }

    public function test_new_professor_can_access_panel(): void
    {
        $teacher = app(TeacherService::class)->store([
            'name' => 'Treinador Novo',
            'email' => 'novo@exemplo.com',
            'phone' => null,
            'specialty' => null,
            'password' => 'senha12345',
        ]);

        $this->actingAs($teacher->user);

        // resolveTenantId() > 0 → painel renderiza no próprio tenant (vazio).
        $this->get(route('admin'))->assertOk();
    }

    public function test_ensure_profile_is_idempotent_for_legacy_user_form(): void
    {
        $user = User::factory(['role' => 'teacher', 'status' => 1])->create();

        // Usuário criado pela tela legada de usuários, sem perfil de tenant.
        $this->assertSame(0, Teacher::where('user_id', $user->id)->count());

        $service = app(TeacherService::class);
        $first = $service->ensureProfile($user);

        $this->assertSame($user->id, $first->user_id);
        $this->assertSame($user->name, $first->name);
        $this->assertTrue($first->active);

        $second = $service->ensureProfile($user);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Teacher::where('user_id', $user->id)->count());
    }

    public function test_non_admin_cannot_manage_professors_via_component(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);
        $this->withoutExceptionHandling();

        $this->expectException(AuthorizationException::class);

        Livewire::test(ProfessorForm::class);
    }
}
