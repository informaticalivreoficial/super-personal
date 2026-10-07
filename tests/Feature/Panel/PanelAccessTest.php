<?php

namespace Tests\Feature\Panel;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_admin(): void
    {
        $this->get('/admin')->assertRedirect('/auth/login');
    }

    public function test_teacher_can_access_panel(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user)
            ->get('/admin')
            ->assertOk();
    }

    public function test_platform_admin_can_access_panel(): void
    {
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_student_cannot_access_panel(): void
    {
        $student = User::factory(['role' => 'student'])->create();

        $this->actingAs($student)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_login_redirects_teacher_to_panel(): void
    {
        $teacher = Teacher::factory()->create();

        Livewire::test(Login::class)
            ->set('email', $teacher->user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('admin'));

        $this->assertAuthenticatedAs($teacher->user);
    }

    public function test_login_blocks_student_from_panel(): void
    {
        $student = User::factory(['role' => 'student', 'status' => 1])->create();

        Livewire::test(Login::class)
            ->set('email', $student->email)
            ->set('password', 'password')
            ->call('login');

        $this->assertGuest();
    }

    public function test_login_blocks_inactive_user(): void
    {
        $teacher = Teacher::factory()->create();
        $teacher->user->update(['status' => 0]);

        Livewire::test(Login::class)
            ->set('email', $teacher->user->email)
            ->set('password', 'password')
            ->call('login');

        $this->assertGuest();
    }

    public function test_login_blocks_inactive_teacher(): void
    {
        $teacher = Teacher::factory()->create();
        $teacher->update(['active' => false]);

        Livewire::test(Login::class)
            ->set('email', $teacher->user->email)
            ->set('password', 'password')
            ->call('login');

        $this->assertGuest();
    }

    public function test_login_with_wrong_password_fails(): void
    {
        $teacher = Teacher::factory()->create();

        Livewire::test(Login::class)
            ->set('email', $teacher->user->email)
            ->set('password', 'senha-errada')
            ->call('login');

        $this->assertGuest();
    }

    public function test_register_creates_teacher_tenant_and_logs_in(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Professor Novo')
            ->set('email', 'novo@superpersonal.test')
            ->set('password', 'senha-forte-123')
            ->call('register')
            ->assertRedirect(route('admin'));

        $this->assertAuthenticated();

        $user = User::where('email', 'novo@superpersonal.test')->firstOrFail();

        $this->assertSame('teacher', $user->role->value);
        $this->assertDatabaseHas('teachers', ['user_id' => $user->id]);
    }
}
