<?php

namespace Tests\Feature\Panel;

use App\Enums\UserRole;
use App\Livewire\Dashboard\Students\StudentIndex;
use App\Livewire\Dashboard\Students\StudentMessages;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\TeacherMessage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Mensagens do treinador ao aluno: página dedicada (saiu da ficha do aluno)
 * com filtros, paginação e modal de envio.
 */
class StudentMessagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cria um professor com um aluno (com conta no app).
     *
     * @return array{0: Teacher, 1: Student}
     */
    private function studentWithTeacher(): array
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        return [$teacher, $student];
    }

    /**
     * Grava mensagens direto no canal database (sem passar pelo componente).
     */
    private function fillMessages(Student $student, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $student->user->notify(new TeacherMessage([
                'title' => "Mensagem {$i}",
                'message' => "Corpo da mensagem {$i}",
                'student_id' => $student->id,
                'teacher_id' => $student->teacher_id,
            ]));
        }
    }

    public function test_teacher_sends_message_as_database_notification(): void
    {
        [$teacher, $student] = $this->studentWithTeacher();

        $this->actingAs($teacher->user);

        Livewire::test(StudentMessages::class, ['student' => $student])
            ->set('title', 'Ajuste no treino de terça')
            ->set('message', 'Troquei a sessão de intervalado por tiro curto.')
            ->call('sendMessage')
            ->assertHasNoErrors()
            ->assertDispatched('message-sent')
            ->assertSet('title', null)
            ->assertSet('message', null);

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
        [$teacher, $student] = $this->studentWithTeacher();

        $this->actingAs($teacher->user);

        Livewire::test(StudentMessages::class, ['student' => $student])
            ->set('title', null)
            ->set('message', null)
            ->call('sendMessage')
            ->assertHasErrors(['title', 'message']);

        $this->assertSame(0, $student->user->notifications()->count());
    }

    public function test_sent_message_appears_on_the_messages_page(): void
    {
        [$teacher, $student] = $this->studentWithTeacher();

        $this->actingAs($teacher->user);

        Livewire::test(StudentMessages::class, ['student' => $student])
            ->set('title', 'Cobrança de setembro paga')
            ->set('message', 'Pagamento confirmado, treinos liberados.')
            ->call('sendMessage')
            ->assertHasNoErrors()
            ->assertSee('Cobrança de setembro paga');

        $this->get(route('students.messages', $student))
            ->assertOk()
            ->assertSee('Mensagens de '.$student->name)
            ->assertSee('Cobrança de setembro paga')
            ->assertSee('Não lida');
    }

    public function test_messages_card_left_the_student_show(): void
    {
        [$teacher, $student] = $this->studentWithTeacher();

        $this->actingAs($teacher->user);

        $this->get(route('students.show', $student))
            ->assertOk()
            ->assertDontSee('Mensagens ao aluno')
            ->assertSee(route('students.messages', $student));
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

        $this->get(route('students.messages', $student))
            ->assertOk()
            ->assertSee('não tem acesso ao aplicativo')
            ->assertDontSee('Nova mensagem');
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

    public function test_guest_is_redirected_to_login(): void
    {
        [, $student] = $this->studentWithTeacher();

        $this->get(route('students.messages', $student))
            ->assertRedirect('/auth/login');
    }

    public function test_student_role_is_blocked(): void
    {
        [, $student] = $this->studentWithTeacher();

        $this->actingAs($student->user);

        $this->get(route('students.messages', $student))->assertForbidden();
    }

    public function test_student_from_another_tenant_returns_404(): void
    {
        $teacherA = Teacher::factory()->create();
        $studentB = Student::factory()->forTeacher(Teacher::factory()->create())->create();

        $this->actingAs($teacherA->user);

        $this->get(route('students.messages', $studentB))->assertNotFound();
    }

    public function test_admin_sends_message_preserving_student_ownership(): void
    {
        [$teacher, $student] = $this->studentWithTeacher();

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'status' => 1,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(StudentMessages::class, ['student' => $student])
            ->set('title', 'Aviso da plataforma')
            ->set('message', 'Mensagem enviada pelo administrador.')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $notification = $student->user->notifications()
            ->where('type', TeacherMessage::class)
            ->first();

        $this->assertSame($student->id, $notification->data['student_id']);
        $this->assertSame($teacher->id, $notification->data['teacher_id']);
        $this->assertSame($admin->id, $notification->data['sent_by']);
    }

    public function test_counts_summarize_read_and_unread(): void
    {
        [$teacher, $student] = $this->studentWithTeacher();

        $this->fillMessages($student, 3);
        $student->user->notifications()
            ->where('type', TeacherMessage::class)
            ->latest()
            ->limit(1)
            ->update(['read_at' => now()]);

        $this->actingAs($teacher->user);

        Livewire::test(StudentMessages::class, ['student' => $student])
            ->assertViewHas('counts', fn (array $counts) => $counts['total'] === 3
                && $counts['unread'] === 2
                && $counts['read'] === 1);
    }

    public function test_search_filters_by_title_and_body(): void
    {
        [$teacher, $student] = $this->studentWithTeacher();

        $student->user->notify(new TeacherMessage([
            'title' => 'Ajuste no treino de terça',
            'message' => 'Reduzi o volume da semana.',
            'student_id' => $student->id,
            'teacher_id' => $student->teacher_id,
        ]));
        $student->user->notify(new TeacherMessage([
            'title' => 'Parabens pelo resultado',
            'message' => 'Voce bateu o recorde na prova.',
            'student_id' => $student->id,
            'teacher_id' => $student->teacher_id,
        ]));

        $this->actingAs($teacher->user);

        // No título
        Livewire::test(StudentMessages::class, ['student' => $student])
            ->set('search', 'treino')
            ->assertViewHas('messages', fn ($messages) => $messages->total() === 1)
            ->assertSee('Ajuste no treino de terça')
            ->assertDontSee('Parabens pelo resultado');

        // No corpo
        Livewire::test(StudentMessages::class, ['student' => $student])
            ->set('search', 'recorde')
            ->assertViewHas('messages', fn ($messages) => $messages->total() === 1)
            ->assertSee('Parabens pelo resultado');

        // Sem resultado
        Livewire::test(StudentMessages::class, ['student' => $student])
            ->set('search', 'inexistente')
            ->assertViewHas('messages', fn ($messages) => $messages->total() === 0)
            ->assertSee('Nenhuma mensagem encontrada com os filtros atuais.');
    }

    public function test_status_filter_separates_read_from_unread(): void
    {
        [$teacher, $student] = $this->studentWithTeacher();

        $this->fillMessages($student, 3);
        $student->user->notifications()
            ->where('type', TeacherMessage::class)
            ->latest()
            ->limit(1)
            ->update(['read_at' => now()]);

        $this->actingAs($teacher->user);

        Livewire::test(StudentMessages::class, ['student' => $student])
            ->set('status', 'unread')
            ->assertViewHas('messages', fn ($messages) => $messages->total() === 2);

        Livewire::test(StudentMessages::class, ['student' => $student])
            ->set('status', 'read')
            ->assertViewHas('messages', fn ($messages) => $messages->total() === 1);

        Livewire::test(StudentMessages::class, ['student' => $student])
            ->assertViewHas('messages', fn ($messages) => $messages->total() === 3);
    }

    public function test_messages_are_paginated(): void
    {
        [$teacher, $student] = $this->studentWithTeacher();

        $this->fillMessages($student, 11);

        $this->actingAs($teacher->user);

        Livewire::test(StudentMessages::class, ['student' => $student])
            ->call('gotoPage', 2, 'mensagens')
            ->assertViewHas('messages', fn ($messages) => $messages->count() === 1
                && $messages->currentPage() === 2);

        Livewire::test(StudentMessages::class, ['student' => $student])
            ->assertViewHas('messages', fn ($messages) => $messages->total() === 11
                && $messages->count() === 10
                && $messages->currentPage() === 1);
    }

    public function test_filters_reset_the_page_and_can_be_cleared(): void
    {
        [$teacher, $student] = $this->studentWithTeacher();

        $this->fillMessages($student, 11);

        $this->actingAs($teacher->user);

        $component = Livewire::test(StudentMessages::class, ['student' => $student]);

        // Página 2 cheia: mudar um filtro volta para a página 1
        $component->call('gotoPage', 2, 'mensagens')
            ->assertViewHas('messages', fn ($messages) => $messages->currentPage() === 2);

        $component->set('search', 'Mensagem')
            ->assertViewHas('messages', fn ($messages) => $messages->currentPage() === 1);

        $component->set('status', 'unread')->assertSet('status', 'unread');

        $component->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('status', '')
            ->assertViewHas('messages', fn ($messages) => $messages->total() === 11);
    }

    public function test_open_modal_clears_previous_draft(): void
    {
        [$teacher, $student] = $this->studentWithTeacher();

        $this->actingAs($teacher->user);

        Livewire::test(StudentMessages::class, ['student' => $student])
            ->set('title', 'Rascunho antigo')
            ->set('message', 'Texto que ficou no formulário.')
            ->call('openModal')
            ->assertDispatched('open-message-modal')
            ->assertSet('title', null)
            ->assertSet('message', null)
            ->assertHasNoErrors();
    }

    public function test_student_list_shows_messages_icon_with_unread_badge(): void
    {
        [$teacher, $student] = $this->studentWithTeacher();

        $this->fillMessages($student, 2);

        // Outro aluno do mesmo treinador sem mensagens
        $otherStudent = Student::factory()->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        Livewire::test(StudentIndex::class)
            ->assertSee(route('students.messages', $student))
            ->assertSee(route('students.messages', $otherStudent))
            ->assertViewHas('unreadMessages', fn (array $counts) => $counts[$student->id] === 2
                && $counts[$otherStudent->id] === 0);
    }

    public function test_unread_counts_do_not_leak_between_tenants(): void
    {
        [$teacher, $student] = $this->studentWithTeacher();

        $this->fillMessages($student, 3);

        $otherTeacher = Teacher::factory()->create();
        $otherStudent = Student::factory()->forTeacher($otherTeacher)->create();
        $this->fillMessages($otherStudent, 1);

        $this->actingAs($otherTeacher->user);

        Livewire::test(StudentIndex::class)
            ->assertViewHas('unreadMessages', fn (array $counts) => ($counts[$otherStudent->id] ?? 0) === 1
                && ! array_key_exists($student->id, $counts));
    }
}
