<?php

namespace Tests\Feature\Api;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_for_valid_credentials(): void
    {
        $teacher = Teacher::factory()->create();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $teacher->user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'role']])
            ->assertJsonPath('user.role', 'teacher');

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $teacher = Teacher::factory()->create();

        $this->postJson('/api/v1/auth/login', [
            'email' => $teacher->user->email,
            'password' => 'senha-errada',
        ])->assertStatus(401);
    }

    public function test_login_validates_required_fields(): void
    {
        $this->postJson('/api/v1/auth/login', [])->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_access_protected_endpoints(): void
    {
        $this->getJson('/api/v1/teacher/students')->assertStatus(401);
        $this->getJson('/api/v1/student/dashboard')->assertStatus(401);
    }

    public function test_student_cannot_access_teacher_endpoints(): void
    {
        Sanctum::actingAs(User::factory(['role' => 'student'])->create());

        $this->getJson('/api/v1/teacher/students')->assertStatus(403);
        $this->getJson('/api/v1/teacher/dashboard')->assertStatus(403);
    }

    public function test_teacher_cannot_access_student_endpoints(): void
    {
        Sanctum::actingAs(User::factory(['role' => 'teacher'])->create());

        $this->getJson('/api/v1/student/dashboard')->assertStatus(403);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $teacher = Teacher::factory()->create();

        Sanctum::actingAs($teacher->user);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $teacher->user->id)
            ->assertJsonPath('data.role', 'teacher');
    }

    public function test_logout_revokes_the_token(): void
    {
        $teacher = Teacher::factory()->create();

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $teacher->user->email,
            'password' => 'password',
        ])->json('token');

        $this->postJson('/api/v1/auth/logout', [], ['Authorization' => 'Bearer '.$token])
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'api-token']);

        // O guard memoriza o usuário entre requests no mesmo teste; força revalidação.
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$token])
            ->assertStatus(401);
    }
}
