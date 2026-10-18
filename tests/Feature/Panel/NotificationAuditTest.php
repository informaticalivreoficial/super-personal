<?php

namespace Tests\Feature\Panel;

use App\Enums\UserRole;
use App\Livewire\Dashboard\Notifications\NotificationIndex;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\NewTrainingAvailable;
use App\Notifications\PaymentDueSoon;
use App\Notifications\PaymentOverdue;
use App\Notifications\TeacherMessage;
use App\Notifications\TrainingToday;
use App\Services\StudentMessageService;
use App\Services\StudentNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Auditoria das notificações enviadas aos alunos (somente leitura):
 * mensagens do treinador, planos publicados e lembretes automáticos.
 */
class NotificationAuditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{teacher: Teacher, studentA: Student, studentB: Student}
     */
    private function arrange(): array
    {
        $teacher = Teacher::factory()->create();
        $studentA = Student::factory()->forTeacher($teacher)->create();
        $studentB = Student::factory()->forTeacher($teacher)->create();

        return compact('teacher', 'studentA', 'studentB');
    }

    /**
     * Grava um aviso como o canal `database` faria (tipo = classe do aviso).
     */
    private function push(
        Student $student,
        string $type,
        string $title,
        string $message,
        ?string $createdAt = null,
        ?string $readAt = null,
    ): DatabaseNotification {
        return $student->user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => $type,
            'data' => ['title' => $title, 'message' => $message],
            'read_at' => $readAt,
            'created_at' => $createdAt ?? now()->toDateTimeString(),
        ]);
    }

    public function test_page_lists_the_notifications_sent_to_the_tenants_students(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA] = $this->arrange();

        // Mensagem real (mesmo caminho do painel) e um lembrete automático.
        app(StudentMessageService::class)->send($studentA, [
            'title' => 'Ajuste na planilha',
            'message' => 'Troquei o treino de tiro de amanhã.',
        ]);
        $studentA->user->notify(new PaymentOverdue([
            'title' => 'Cobrança em atraso',
            'message' => 'Sua mensalidade venceu.',
        ]));

        $this->actingAs($teacher->user);

        Livewire::test(NotificationIndex::class)
            ->assertOk()
            ->assertSee('Notificações Enviadas')
            ->assertSee('Ajuste na planilha')
            ->assertSee('Troquei o treino de tiro de amanhã.')
            ->assertSee('Cobrança em atraso')
            ->assertSee('Sua mensalidade venceu.')
            ->assertSee('Mensagem do treinador')
            ->assertSee($studentA->name)
            ->assertSee('2 aviso(s)')
            ->assertSee('Aguardando leitura');
    }

    public function test_notifications_of_other_teachers_students_are_not_shown(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA] = $this->arrange();

        $foreignStudent = Student::factory()->forTeacher(Teacher::factory()->create())->create();

        $this->push($studentA, TeacherMessage::class, 'Meu aviso', 'Aparece para o dono');
        $this->push($foreignStudent, TeacherMessage::class, 'Aviso de outro treinador', 'Não pode vazar');

        $this->actingAs($teacher->user);

        Livewire::test(NotificationIndex::class)
            ->assertSee('Meu aviso')
            ->assertDontSee('Aviso de outro treinador');

        // O aluno de outro treinador nem aparece no filtro.
        $component = Livewire::test(NotificationIndex::class);
        $this->assertTrue($component->viewData('studentOptions')->has($studentA->id));
        $this->assertFalse($component->viewData('studentOptions')->has($foreignStudent->id));

        $service = app(StudentNotificationService::class);
        $this->assertSame(1, $service->counts($service->filters([]))['total']);
    }

    public function test_filters_by_student(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA, 'studentB' => $studentB] = $this->arrange();

        $this->push($studentA, TeacherMessage::class, 'Aviso da aluna A', 'texto A');
        $this->push($studentB, TeacherMessage::class, 'Aviso do aluno B', 'texto B');

        $this->actingAs($teacher->user);

        Livewire::test(NotificationIndex::class)
            ->set('filterStudentId', $studentB->id)
            ->assertSee('Aviso do aluno B')
            ->assertDontSee('Aviso da aluna A')
            ->set('filterStudentId', null)
            ->assertSee('Aviso da aluna A');
    }

    public function test_filters_by_type(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA] = $this->arrange();

        $this->push($studentA, TeacherMessage::class, 'Mensagem direta', 'texto');
        $this->push($studentA, TrainingToday::class, 'Treino de hoje', 'você tem treino hoje');

        $this->actingAs($teacher->user);

        Livewire::test(NotificationIndex::class)
            ->set('filterType', TrainingToday::class)
            ->assertSee('Treino de hoje')
            ->assertDontSee('Mensagem direta')
            ->set('filterType', TeacherMessage::class)
            ->assertSee('Mensagem direta')
            ->assertDontSee('você tem treino hoje');
    }

    public function test_filters_by_read_status(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA] = $this->arrange();

        $this->push($studentA, PaymentDueSoon::class, 'Cobrança a vencer', 'vence em 2 dias');
        $this->push(
            $studentA,
            NewTrainingAvailable::class,
            'Novo treino disponível',
            'o plano X está no ar',
            readAt: now()->toDateTimeString(),
        );

        $this->actingAs($teacher->user);

        // As asserções usam o corpo do aviso (único por linha): os rótulos de
        // tipo aparecem sempre no <select> de filtros, não servem de prova.
        $component = Livewire::test(NotificationIndex::class)
            ->set('filterStatus', 'unread')
            ->assertSee('vence em 2 dias')
            ->assertDontSee('o plano X está no ar')
            ->assertSee('Não lida');

        $this->assertSame(1, $component->viewData('notifications')->total());

        Livewire::test(NotificationIndex::class)
            ->set('filterStatus', 'read')
            ->assertSee('o plano X está no ar')
            ->assertDontSee('vence em 2 dias')
            ->assertSee('Lida');
    }

    public function test_search_matches_title_and_body(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA] = $this->arrange();

        $this->push($studentA, TeacherMessage::class, 'Ajuste na planilha', 'Troquei o tiro de amanhã.');
        $this->push($studentA, PaymentDueSoon::class, 'Cobrança a vencer', 'Pague pelo aplicativo.');

        $this->actingAs($teacher->user);

        Livewire::test(NotificationIndex::class)
            // Busca pelo título, com acento (o JSON guarda `\u00e7`).
            ->set('search', 'cobrança')
            ->assertSee('Cobrança a vencer')
            ->assertDontSee('Ajuste na planilha')
            // Busca pelo corpo da mensagem.
            ->set('search', 'tiro de amanhã')
            ->assertSee('Ajuste na planilha')
            ->assertDontSee('Pague pelo aplicativo.');
    }

    public function test_filters_by_period(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA] = $this->arrange();

        $antigo = $this->push($studentA, PaymentOverdue::class, 'Aviso antigo', 'do mês passado');
        $antigo->forceFill(['created_at' => now()->subMonthNoOverflow()->startOfMonth()->addDays(5)])->save();

        $this->push($studentA, PaymentDueSoon::class, 'Aviso recente', 'de hoje');

        $this->actingAs($teacher->user);

        Livewire::test(NotificationIndex::class)
            ->set('from', now()->subDays(7)->toDateString())
            ->assertSee('Aviso recente')
            ->assertDontSee('Aviso antigo')
            // Datas invertidas são trocadas pelo service (não escondem nada).
            ->set('from', now()->toDateString())
            ->set('to', now()->subDays(7)->toDateString())
            ->assertSee('Aviso recente');
    }

    public function test_counts_and_type_summary(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA, 'studentB' => $studentB] = $this->arrange();

        $this->push($studentA, TeacherMessage::class, 'Mensagem 1', 'a');
        $this->push($studentA, TeacherMessage::class, 'Mensagem 2', 'b', readAt: now()->toDateTimeString());
        $this->push($studentB, TrainingToday::class, 'Treino de hoje', 'c');

        $this->actingAs($teacher->user);

        $service = app(StudentNotificationService::class);
        $filters = $service->filters([]);

        $this->assertSame(['total' => 3, 'unread' => 2, 'read' => 1], $service->counts($filters));

        $this->assertSame([
            ['type' => TeacherMessage::class, 'label' => 'Mensagem do treinador', 'count' => 2],
            ['type' => TrainingToday::class, 'label' => 'Treino de hoje', 'count' => 1],
        ], $service->typeCounts($filters));

        Livewire::test(NotificationIndex::class)
            ->assertSee('Avisos no recorte')
            ->assertSee('Mensagem do treinador · 2')
            ->assertSee('Treino de hoje · 1')
            ->assertSee('3 aviso(s)');
    }

    public function test_list_is_paginated(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA] = $this->arrange();

        for ($i = 0; $i < 16; $i++) {
            $this->push($studentA, TeacherMessage::class, 'Aviso '.$i, 'texto');
        }

        $this->actingAs($teacher->user);

        $component = Livewire::test(NotificationIndex::class)
            ->assertSee('16 aviso(s)');

        $this->assertSame(15, $component->viewData('notifications')->count());
        $this->assertSame(16, $component->viewData('notifications')->total());
    }

    public function test_clear_filters_resets_the_page(): void
    {
        ['teacher' => $teacher, 'studentA' => $studentA] = $this->arrange();

        $this->push($studentA, TeacherMessage::class, 'Aviso qualquer', 'texto');

        $this->actingAs($teacher->user);

        Livewire::test(NotificationIndex::class)
            ->set('search', 'planilha')
            ->set('filterStudentId', $studentA->id)
            ->set('filterType', TeacherMessage::class)
            ->set('filterStatus', 'unread')
            ->set('from', now()->subDays(3)->toDateString())
            ->set('to', now()->toDateString())
            ->assertSee('Nenhum aviso encontrado com os filtros atuais.')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('filterStudentId', null)
            ->assertSet('filterType', '')
            ->assertSet('filterStatus', '')
            ->assertSet('from', '')
            ->assertSet('to', '')
            ->assertSee('Aviso qualquer');
    }

    public function test_empty_state_without_notifications(): void
    {
        ['teacher' => $teacher] = $this->arrange();

        $this->actingAs($teacher->user);

        Livewire::test(NotificationIndex::class)
            ->assertOk()
            ->assertSee('Nenhum aviso enviado ainda.')
            ->assertSee('0 aviso(s)');
    }

    public function test_service_ignores_invalid_filters(): void
    {
        $service = app(StudentNotificationService::class);

        $filters = $service->filters([
            'type' => 'App\\Notifications\\NaoExiste',
            'status' => 'qualquer',
            'student_id' => 0,
            'from' => 'data-invalida',
            'to' => '',
        ]);

        $this->assertNull($filters['type']);
        $this->assertSame('', $filters['status']);
        $this->assertNull($filters['student_id']);
        $this->assertNull($filters['from']);
        $this->assertNull($filters['to']);

        $this->assertSame('Aviso', $service->typeLabel('App\\Notifications\\NaoExiste'));
        $this->assertSame('Mensagem do treinador', $service->typeLabel(TeacherMessage::class));
    }

    public function test_access_and_sidebar_link(): void
    {
        $teacher = Teacher::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'status' => 1]);

        // Convidado → login.
        $this->get(route('notifications.index'))->assertRedirect(route('login'));

        // Treinador → 200 com o link no menu.
        $this->actingAs($teacher->user);
        $this->get(route('notifications.index'))
            ->assertOk()
            ->assertSee(route('notifications.index'));

        // Admin da plataforma não tem alunos → 403.
        $this->actingAs($admin);
        $this->get(route('notifications.index'))->assertForbidden();
    }
}
