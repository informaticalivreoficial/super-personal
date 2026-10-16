<?php

namespace Tests\Feature\Api;

use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Edição do próprio perfil pelo aluno: CPF (usado no Pix) e data de
 * nascimento com a regra de maioridade (18+).
 */
class StudentProfileTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $teacher = Teacher::factory()->create();
        $this->student = Student::factory()->forTeacher($teacher)->create([
            'document' => null,
            'birth_date' => null,
        ]);

        Sanctum::actingAs($this->student->user);
    }

    public function test_student_updates_the_profile_with_cpf_and_birth_date(): void
    {
        $response = $this->putJson('/api/v1/student/profile', [
            'name' => 'Aluno Atualizado',
            'phone' => '(12) 99999-0000',
            'gender' => 'male',
            'height' => 180,
            'document' => '123.456.789-00',
            'birth_date' => '1990-05-10',
            'available_days' => [2, 4, 6],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.document', '123.456.789-00')
            ->assertJsonPath('data.birth_date', '1990-05-10');

        $this->student->refresh();
        $this->assertSame('123.456.789-00', $this->student->document);
        $this->assertSame('1990-05-10', $this->student->birth_date->format('Y-m-d'));
        $this->assertSame('Aluno Atualizado', $this->student->name);
        $this->assertSame(180, $this->student->height);
        $this->assertSame([2, 4, 6], $this->student->available_days);
    }

    public function test_profile_show_returns_document_and_birth_date(): void
    {
        $this->student->update([
            'document' => '12345678900',
            'birth_date' => '1988-03-02',
        ]);

        $this->getJson('/api/v1/student/profile')
            ->assertOk()
            ->assertJsonPath('data.document', '12345678900')
            ->assertJsonPath('data.birth_date', '1988-03-02');
    }

    public function test_student_under_18_is_rejected(): void
    {
        $this->putJson('/api/v1/student/profile', [
            'birth_date' => now()->subYears(17)->toDateString(),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('birth_date');

        $this->assertNull($this->student->fresh()->birth_date);
    }

    public function test_student_turning_18_today_is_accepted(): void
    {
        $this->putJson('/api/v1/student/profile', [
            'birth_date' => now()->subYears(18)->toDateString(),
        ])->assertOk();

        $this->assertNotNull($this->student->fresh()->birth_date);
    }

    public function test_birth_date_in_an_invalid_format_is_rejected(): void
    {
        $this->putJson('/api/v1/student/profile', ['birth_date' => '10/05/1990'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('birth_date');
    }

    public function test_cpf_must_have_11_digits(): void
    {
        $this->putJson('/api/v1/student/profile', ['document' => '123'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('document');

        $this->putJson('/api/v1/student/profile', ['document' => '12'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('document');

        $this->assertNull($this->student->fresh()->document);
    }

    public function test_student_cannot_change_the_email_or_trainer_fields(): void
    {
        $original = [
            'email' => $this->student->email,
            'teacher_id' => $this->student->teacher_id,
            'current_weight' => $this->student->current_weight,
        ];

        $this->putJson('/api/v1/student/profile', [
            'name' => 'Aluno Atualizado',
            'email' => 'invasor@example.com',
            'teacher_id' => 999,
            'current_weight' => 40,
            'active' => false,
        ])->assertOk();

        $this->student->refresh();
        $this->assertSame('Aluno Atualizado', $this->student->name);
        $this->assertSame($original['email'], $this->student->email);
        $this->assertSame($original['teacher_id'], $this->student->teacher_id);
        $this->assertSame($original['current_weight'], $this->student->current_weight);
        $this->assertTrue($this->student->active);
    }

    /**
     * O nome do aluno vive em `students` e em `users.name` (sessão do app): o
     * app mostra a home a partir da própria conta, então os dois andam juntos.
     *
     * A conferência é no banco porque o `Sanctum::actingAs` memoiza a instância
     * do usuário na guarda — a resposta de `/auth/me` repetiria o objeto antigo
     * (artefato do teste; em produção cada requisição reautentica do banco).
     */
    public function test_profile_update_keeps_the_login_name_in_sync(): void
    {
        $this->putJson('/api/v1/student/profile', ['name' => 'Novo Nome'])
            ->assertOk();

        $this->assertSame('Novo Nome', $this->student->fresh()->name);
        $this->assertSame('Novo Nome', $this->student->user->fresh()->name);
    }
}
