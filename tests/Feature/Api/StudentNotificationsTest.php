<?php

namespace Tests\Feature\Api;

use App\Models\Student;
use App\Notifications\TeacherMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Notificações do aluno: listagem e leitura (individual e em lote).
 *
 * O isolamento vem de `auth()->user()->notifications()`: o aluno só alcança as
 * próprias notificações (as de outro usuário caem em 404).
 */
class StudentNotificationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cria uma notificação database para o aluno e devolve o id (uuid).
     */
    private function notifyStudent(Student $student, string $title = 'Mensagem do treinador'): string
    {
        $student->user->notify(new TeacherMessage([
            'title' => $title,
            'message' => 'Treino novo no ar.',
            'student_id' => $student->id,
            'teacher_id' => $student->teacher_id,
            'sent_by' => $student->teacher_id,
        ]));

        return $student->user->notifications()->latest()->first()->id;
    }

    public function test_notification_list_caps_per_page(): void
    {
        $student = Student::factory()->create();
        $this->notifyStudent($student);

        Sanctum::actingAs($student->user);

        $this->getJson('/api/v1/student/notifications?per_page=500')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_student_lists_own_notifications(): void
    {
        $student = Student::factory()->create();
        $this->notifyStudent($student, 'Bem-vindo');

        $other = Student::factory()->create();
        $this->notifyStudent($other, 'Do outro aluno');

        Sanctum::actingAs($student->user);

        $this->getJson('/api/v1/student/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.data.title', 'Bem-vindo')
            ->assertJsonPath('data.0.read_at', null);
    }

    public function test_student_marks_a_notification_as_read(): void
    {
        $student = Student::factory()->create();
        $id = $this->notifyStudent($student);

        Sanctum::actingAs($student->user);

        $response = $this->postJson("/api/v1/student/notifications/{$id}/read");

        $response->assertOk()->assertJsonPath('data.id', $id);

        $this->assertNotNull($response->json('data.read_at'));
        $this->assertNotNull(
            $student->user->notifications()->whereKey($id)->first()->read_at
        );
    }

    public function test_student_cannot_mark_somebody_elses_notification(): void
    {
        $student = Student::factory()->create();
        $other = Student::factory()->create();
        $id = $this->notifyStudent($other);

        Sanctum::actingAs($student->user);

        $this->postJson("/api/v1/student/notifications/{$id}/read")->assertNotFound();

        $this->assertNull($other->user->notifications()->whereKey($id)->first()->read_at);
    }

    public function test_marking_an_unknown_notification_returns_404(): void
    {
        $student = Student::factory()->create();

        Sanctum::actingAs($student->user);

        $this->postJson('/api/v1/student/notifications/'.Str::uuid().'/read')
            ->assertNotFound();
    }

    public function test_marking_a_non_uuid_notification_returns_404(): void
    {
        $student = Student::factory()->create();

        Sanctum::actingAs($student->user);

        $this->postJson('/api/v1/student/notifications/abc/read')->assertNotFound();
    }

    public function test_student_marks_all_own_notifications_as_read(): void
    {
        $student = Student::factory()->create();
        $this->notifyStudent($student, 'Um');
        $this->notifyStudent($student, 'Dois');

        $other = Student::factory()->create();
        $otherId = $this->notifyStudent($other);

        Sanctum::actingAs($student->user);

        $this->postJson('/api/v1/student/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.marked', 2);

        $this->assertSame(0, $student->user->unreadNotifications()->count());
        $this->assertNull(
            $other->user->notifications()->whereKey($otherId)->first()->read_at
        );
    }

    public function test_mark_all_without_unread_is_a_noop(): void
    {
        $student = Student::factory()->create();
        $id = $this->notifyStudent($student);
        $student->user->notifications()->whereKey($id)->first()->markAsRead();

        Sanctum::actingAs($student->user);

        $this->postJson('/api/v1/student/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.marked', 0);
    }

    public function test_guest_cannot_touch_notifications(): void
    {
        $id = Str::uuid();

        $this->getJson('/api/v1/student/notifications')->assertUnauthorized();
        $this->postJson('/api/v1/student/notifications/read-all')->assertUnauthorized();
        $this->postJson("/api/v1/student/notifications/{$id}/read")->assertUnauthorized();
    }
}
