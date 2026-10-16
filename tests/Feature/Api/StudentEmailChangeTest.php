<?php

namespace Tests\Feature\Api;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\EmailChangeCode;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Troca do e-mail de acesso pelo aluno: o endereço novo só passa a valer com o
 * código de 6 dígitos enviado para ele (`users.email` não muda antes disso).
 */
class StudentEmailChangeTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $teacher = Teacher::factory()->create();
        $this->student = Student::factory()->forTeacher($teacher)->create();

        // Cadastro do aluno e conta de acesso com o mesmo e-mail (o convite do
        // treinador cria a conta com o e-mail do aluno).
        $this->student->user->update(['email' => 'aluno@superpersonal.test']);
        $this->student->update(['email' => 'aluno@superpersonal.test']);
    }

    private function actingAsStudent(): void
    {
        Sanctum::actingAs($this->student->user);
    }

    /** Pede a troca e devolve o código que "chegou" no e-mail novo. */
    private function requestChange(string $email = 'novo@superpersonal.test'): string
    {
        Notification::fake();

        $this->actingAsStudent();

        $this->postJson('/api/v1/student/profile/email', ['email' => $email])
            ->assertOk()
            ->assertJsonPath('pending_email', $email);

        $code = null;

        Notification::assertSentOnDemand(
            EmailChangeCode::class,
            function (EmailChangeCode $notification, array $channels, $notifiable) use (&$code, $email) {
                $code = $notification->code;

                return ($notifiable->routes['mail'] ?? null) === $email;
            }
        );

        $this->assertNotNull($code, 'Nenhum código foi enviado para o novo e-mail.');

        return $code;
    }

    public function test_requesting_a_change_keeps_the_current_email_until_confirmation(): void
    {
        $code = $this->requestChange();

        $user = $this->student->user->fresh();

        $this->assertSame('novo@superpersonal.test', $user->pending_email);
        // O e-mail em uso continua o antigo e o código vai com hash no banco.
        $this->assertSame('aluno@superpersonal.test', $user->email);
        $this->assertNotSame($code, $user->email_change_code);
        $this->assertTrue(password_verify($code, $user->email_change_code));
        $this->assertTrue($user->email_change_expires_at->isFuture());
    }

    public function test_confirming_the_code_switches_the_email_everywhere(): void
    {
        $code = $this->requestChange();

        $this->postJson('/api/v1/student/profile/email/confirm', ['code' => $code])
            ->assertOk()
            ->assertJsonPath('user.email', 'novo@superpersonal.test')
            ->assertJsonPath('user.pending_email', null);

        $user = $this->student->user->fresh();
        $this->assertSame('novo@superpersonal.test', $user->email);
        $this->assertNull($user->pending_email);
        $this->assertNull($user->email_change_code);
        $this->assertNotNull($user->email_verified_at);

        // O cadastro do aluno acompanha (é o mesmo e-mail do aluno).
        $this->assertSame('novo@superpersonal.test', $this->student->fresh()->email);

        // A sessão continua valendo e o login passa a usar o endereço novo.
        $this->getJson('/api/v1/auth/me')->assertOk()
            ->assertJsonPath('data.email', 'novo@superpersonal.test');
    }

    public function test_wrong_code_is_rejected_and_counts_the_attempt(): void
    {
        $code = $this->requestChange();

        $wrong = $code === '000000' ? '111111' : '000000';

        $this->postJson('/api/v1/student/profile/email/confirm', ['code' => $wrong])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');

        $user = $this->student->user->fresh();
        $this->assertSame(1, (int) $user->email_change_attempts);
        $this->assertSame('aluno@superpersonal.test', $user->email);
    }

    public function test_expired_code_needs_a_new_request(): void
    {
        $code = $this->requestChange();

        $user = $this->student->user;
        $user->email_change_expires_at = now()->subMinute();
        $user->save();

        $this->postJson('/api/v1/student/profile/email/confirm', ['code' => $code])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');

        // Expirado limpa o pedido: o aluno pede outro código.
        $user->refresh();
        $this->assertNull($user->pending_email);
        $this->assertSame('aluno@superpersonal.test', $user->email);
    }

    public function test_too_many_wrong_attempts_require_a_new_code(): void
    {
        $code = $this->requestChange();

        $user = $this->student->user;
        $user->email_change_attempts = ProfileService::EMAIL_CODE_MAX_ATTEMPTS;
        $user->save();

        $this->postJson('/api/v1/student/profile/email/confirm', ['code' => $code])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');

        $this->assertSame('aluno@superpersonal.test', $user->fresh()->email);
    }

    public function test_email_already_in_use_is_rejected(): void
    {
        Notification::fake();

        User::factory()->create(['email' => 'ocupado@superpersonal.test']);

        $this->actingAsStudent();

        $this->postJson('/api/v1/student/profile/email', ['email' => 'ocupado@superpersonal.test'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        Notification::assertNothingSent();

        $user = $this->student->user->fresh();
        $this->assertNull($user->pending_email);
        $this->assertSame('aluno@superpersonal.test', $user->email);
    }

    public function test_email_of_another_student_cannot_be_used(): void
    {
        Student::factory()->forTeacher(Teacher::factory()->create())->create([
            'email' => 'outro.aluno@superpersonal.test',
        ]);

        $this->actingAsStudent();

        $this->postJson('/api/v1/student/profile/email', ['email' => 'outro.aluno@superpersonal.test'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_current_email_is_rejected(): void
    {
        $this->actingAsStudent();

        $this->postJson('/api/v1/student/profile/email', ['email' => 'aluno@superpersonal.test'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_confirm_is_rejected_when_the_address_was_taken_meanwhile(): void
    {
        $code = $this->requestChange();

        User::factory()->create(['email' => 'novo@superpersonal.test']);

        $this->postJson('/api/v1/student/profile/email/confirm', ['code' => $code])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $user = $this->student->user->fresh();
        $this->assertSame('aluno@superpersonal.test', $user->email);
        $this->assertNull($user->pending_email);
    }

    public function test_confirm_without_a_pending_change_is_rejected(): void
    {
        $this->actingAsStudent();

        $this->postJson('/api/v1/student/profile/email/confirm', ['code' => '123456'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_cancel_clears_the_pending_change(): void
    {
        $this->requestChange();

        $this->deleteJson('/api/v1/student/profile/email')->assertOk();

        $user = $this->student->user->fresh();
        $this->assertNull($user->pending_email);
        $this->assertNull($user->email_change_code);
    }

    public function test_requires_authentication(): void
    {
        $this->postJson('/api/v1/student/profile/email', ['email' => 'novo@superpersonal.test'])
            ->assertUnauthorized();
    }
}
