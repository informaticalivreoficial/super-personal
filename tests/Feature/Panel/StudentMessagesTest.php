<?php

namespace Tests\Feature\Panel;

use App\Livewire\Dashboard\Students\StudentMessages;
use App\Models\Student;
use App\Models\Teacher;
use App\Notifications\TeacherMessage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_sends_message_as_database_notification(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        Livewire::test(StudentMessages::class, ['student' => $student])
            ->set('title', 'Ajuste no treino de terça')
            ->set('message', 'Troquei a sessão de intervalado por tiro curto.')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $notification = $student->user->notifications()
            ->where('type', TeacherMessage::class)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame('Ajuste no treino de terça', $notification->data['title']);
        $this->assertSame('Troquei a sessão de intervalado por tiro curto.', $notification->data['message']);
        $this->assertSame($student->id, $notification->data['student_id']);
        $this->assertSame($teacher->id, $notification->data['teacher_id']);
        $this->assertNull($notification->read_at);
    }

    public function test_message_requires_title_and_body(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        Livewire::test(StudentMessages::class, ['student' => $student])
            ->set('title', null)
            ->set('message', null)
            ->call('sendMessage')
            ->assertHasErrors(['title', 'message']);

        $this->assertSame(0, $student->user->notifications()->count());
    }

    public function test_sent_message_appears_on_student_show(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        Livewire::test(StudentMessages::class, ['student' => $student])
            ->set('title', 'Cobrança de setembro paga')
            ->set('message', 'Pagamento confirmado, treinos liberados.')
            ->call('sendMessage')
            ->assertHasNoErrors()
            ->assertSee('Cobrança de setembro paga');

        $this->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('Mensagens ao aluno')
            ->assertSee('Cobrança de setembro paga');
    }

    public function test_student_without_app_user_cannot_receive_message(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create(['user_id' => null]);

        $this->actingAs($teacher->user);

        Livewire::test(StudentMessages::class, ['student' => $student])
            ->set('title', 'Olá')
            ->set('message', 'Mensagem sem destinatário.')
            ->call('sendMessage')
            ->assertHasErrors(['message']);
    }

    public function test_component_denies_student_of_another_tenant(): void
    {
        $teacherA = Teacher::factory()->create();
        $studentB = Student::factory()->forTeacher(Teacher::factory()->create())->create();

        $this->actingAs($teacherA->user);
        $this->withoutExceptionHandling();

        $this->expectException(AuthorizationException::class);

        Livewire::test(StudentMessages::class, ['student' => $studentB]);
    }
}
