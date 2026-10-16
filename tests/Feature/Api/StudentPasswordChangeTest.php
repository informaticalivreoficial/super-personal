<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Troca da própria senha pelo aluno (app): exige a senha atual, valida a nova
 * (8+ e confirmação) e derruba as outras sessões abertas.
 */
class StudentPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $teacher = Teacher::factory()->create();
        $this->student = Student::factory()->forTeacher($teacher)->create();

        $this->student->user->update(['password' => 'senha-atual']);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'current_password' => 'senha-atual',
            'password' => 'nova-senha-123',
            'password_confirmation' => 'nova-senha-123',
        ], $override);
    }

    public function test_student_changes_the_password_with_the_current_one(): void
    {
        Sanctum::actingAs($this->student->user);

        $this->putJson('/api/v1/student/profile/password', $this->payload())
            ->assertOk();

        $this->assertTrue(
            Hash::check('nova-senha-123', $this->student->user->fresh()->password)
        );

        // A senha nova é a credencial do login.
        $this->postJson('/api/v1/auth/login', [
            'email' => $this->student->user->email,
            'password' => 'nova-senha-123',
        ])->assertOk();
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        Sanctum::actingAs($this->student->user);

        $this->putJson('/api/v1/student/profile/password', $this->payload([
            'current_password' => 'errada',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');

        $this->assertTrue(
            Hash::check('senha-atual', $this->student->user->fresh()->password)
        );
    }

    public function test_new_password_needs_8_chars_and_confirmation(): void
    {
        Sanctum::actingAs($this->student->user);

        $this->putJson('/api/v1/student/profile/password', $this->payload([
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->putJson('/api/v1/student/profile/password', $this->payload([
            'password_confirmation' => 'outra-senha-123',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_current_password_is_required(): void
    {
        Sanctum::actingAs($this->student->user);

        $payload = $this->payload();
        unset($payload['current_password']);

        $this->putJson('/api/v1/student/profile/password', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');
    }

    /**
     * A troca mantém a sessão que pediu (é o aparelho do titular) e revoga as
     * demais — senha trocada em um aparelho derruba o outro.
     */
    public function test_password_change_revokes_the_other_sessions(): void
    {
        $user = $this->student->user;
        $user->createToken('outro-aparelho');
        $token = $user->createToken('este-aparelho')->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/v1/student/profile/password', $this->payload())
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'este-aparelho']);
    }

    public function test_teacher_cannot_use_the_student_endpoint(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'role' => UserRole::TEACHER,
        ]));

        $this->putJson('/api/v1/student/profile/password', $this->payload())
            ->assertForbidden();
    }

    public function test_requires_authentication(): void
    {
        $this->putJson('/api/v1/student/profile/password', $this->payload())
            ->assertUnauthorized();
    }
}
