<?php

namespace Tests\Feature\Api;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentRegisterTest extends TestCase
{
    use RefreshDatabase;

    private function studentWithInvite(Teacher $teacher): Student
    {
        $student = Student::factory()->withoutAccount()->forTeacher($teacher)->create();
        $student->invite_code = 'K7M2P9QX';
        $student->save();

        return $student->refresh();
    }

    public function test_teacher_store_generates_invite_code_and_resource_exposes_it(): void
    {
        $teacher = Teacher::factory()->create();
        Sanctum::actingAs($teacher->user);

        $response = $this->postJson('/api/v1/teacher/students', [
            'name' => 'Aluno Novo',
            'email' => 'aluno-novo@example.com',
        ]);

        $response->assertCreated();

        $student = Student::query()->where('email', 'aluno-novo@example.com')->firstOrFail();
        $this->assertNotNull($student->invite_code);
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{8}$/', $student->invite_code);

        $this->getJson("/api/v1/teacher/students/{$student->id}")
            ->assertOk()
            ->assertJsonPath('data.invite_code', $student->invite_code);
    }

    public function test_student_registers_with_invite_code_and_receives_token(): void
    {
        $teacher = Teacher::factory()->create();
        $student = $this->studentWithInvite($teacher);

        $response = $this->postJson('/api/v1/auth/student-register', [
            'invite_code' => 'k7m2p9qx', // caixa é normalizada pelo serviço
            'password' => 'senha-segura-123',
            'password_confirmation' => 'senha-segura-123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'role']])
            ->assertJsonPath('user.role', 'student');

        $this->assertNotEmpty($response->json('token'));

        $student->refresh();
        $this->assertNotNull($student->user_id);
        $this->assertNull($student->invite_code);

        $user = User::findOrFail($student->user_id);
        $this->assertSame($student->email, $user->email);
        $this->assertSame('student', $user->role->value);
        $this->assertSame(1, (int) $user->status);
        $this->assertTrue(Hash::check('senha-segura-123', $user->password));

        // O token emitido consome os endpoints do app.
        $this->withToken($response->json('token'))->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.role', 'student');
        $this->withToken($response->json('token'))->getJson('/api/v1/student/dashboard')->assertOk();
    }

    public function test_registered_student_can_login_with_email_and_password(): void
    {
        $teacher = Teacher::factory()->create();
        $student = $this->studentWithInvite($teacher);

        $this->postJson('/api/v1/auth/student-register', [
            'invite_code' => $student->invite_code,
            'password' => 'senha-segura-123',
            'password_confirmation' => 'senha-segura-123',
        ])->assertCreated();

        $this->postJson('/api/v1/auth/login', [
            'email' => $student->email,
            'password' => 'senha-segura-123',
        ])->assertOk()->assertJsonPath('user.role', 'student');
    }

    public function test_invalid_invite_code_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/student-register', [
            'invite_code' => 'ZZZZZZZZ',
            'password' => 'senha-segura-123',
            'password_confirmation' => 'senha-segura-123',
        ])->assertStatus(422)->assertJsonValidationErrors(['invite_code']);
    }

    public function test_consumed_invite_code_is_rejected(): void
    {
        $teacher = Teacher::factory()->create();
        $student = $this->studentWithInvite($teacher);

        $payload = [
            'invite_code' => $student->invite_code,
            'password' => 'senha-segura-123',
            'password_confirmation' => 'senha-segura-123',
        ];

        $this->postJson('/api/v1/auth/student-register', $payload)->assertCreated();
        $this->postJson('/api/v1/auth/student-register', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['invite_code']);
    }

    public function test_student_with_account_cannot_register_again(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create(); // já tem conta
        $student->invite_code = 'K7M2P9QX';
        $student->save();

        $this->postJson('/api/v1/auth/student-register', [
            'invite_code' => 'K7M2P9QX',
            'password' => 'senha-segura-123',
            'password_confirmation' => 'senha-segura-123',
        ])->assertStatus(422)->assertJsonValidationErrors(['invite_code']);
    }

    public function test_inactive_student_cannot_register(): void
    {
        $teacher = Teacher::factory()->create();
        $student = $this->studentWithInvite($teacher);
        $student->active = false;
        $student->save();

        $this->postJson('/api/v1/auth/student-register', [
            'invite_code' => $student->invite_code,
            'password' => 'senha-segura-123',
            'password_confirmation' => 'senha-segura-123',
        ])->assertStatus(422)->assertJsonValidationErrors(['invite_code']);
    }

    public function test_register_rejected_when_email_already_used_by_another_account(): void
    {
        $teacher = Teacher::factory()->create();
        $student = $this->studentWithInvite($teacher);
        User::factory()->create(['email' => $student->email]);

        $this->postJson('/api/v1/auth/student-register', [
            'invite_code' => $student->invite_code,
            'password' => 'senha-segura-123',
            'password_confirmation' => 'senha-segura-123',
        ])->assertStatus(422)->assertJsonValidationErrors(['invite_code']);
    }

    public function test_register_validates_password_and_required_fields(): void
    {
        $this->postJson('/api/v1/auth/student-register', [])->assertStatus(422)
            ->assertJsonValidationErrors(['invite_code', 'password']);

        $this->postJson('/api/v1/auth/student-register', [
            'invite_code' => 'K7M2P9QX',
            'password' => 'curta',
            'password_confirmation' => 'curta',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);

        $this->postJson('/api/v1/auth/student-register', [
            'invite_code' => 'K7M2P9QX',
            'password' => 'senha-segura-123',
            'password_confirmation' => 'outra-senha',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_store_student_rejects_email_used_by_another_account(): void
    {
        $teacher = Teacher::factory()->create();
        $existing = User::factory()->create();

        Sanctum::actingAs($teacher->user);

        $this->postJson('/api/v1/teacher/students', [
            'name' => 'Aluno',
            'email' => $existing->email,
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }
}
