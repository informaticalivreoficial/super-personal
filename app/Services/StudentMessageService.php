<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use App\Notifications\TeacherMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Mensagens do treinador ao aluno: notificações do canal `database`
 * (tipo TeacherMessage) entregues ao usuário do aluno — a lista do aluno
 * é a API GET /api/v1/student/notifications. Canal único por enquanto;
 * o push (FCM) entra acrescentando o canal em BaseNotification::via().
 *
 * `teacher_id`/`student_id` vêm sempre do aluno — nunca do request.
 */
class StudentMessageService
{
    /** Filtros da listagem por situação de leitura. */
    public const STATUS_OPTIONS = [
        '' => 'Todas',
        'unread' => 'Não lidas',
        'read' => 'Lidas',
    ];

    /**
     * O aluno tem conta no app? Sem usuário vinculado não há destinatário.
     */
    public function hasAppAccess(Student $student): bool
    {
        return (bool) $student->user;
    }

    /**
     * Envia a mensagem ao aluno.
     *
     * @param  array{title: string, message: string}  $data
     */
    public function send(Student $student, array $data): void
    {
        $user = $student->user;

        if (! $user) {
            throw ValidationException::withMessages([
                'message' => 'Aluno sem acesso ao aplicativo: não há usuário vinculado.',
            ]);
        }

        $user->notify(new TeacherMessage([
            'title' => $data['title'],
            'message' => $data['message'],
            'student_id' => $student->id,
            'teacher_id' => $student->teacher_id,
            'sent_by' => auth()->id(),
        ]));
    }

    /**
     * Mensagens enviadas ao aluno, paginadas e filtradas.
     * O aluno precisa ter conta no app (use hasAppAccess() antes).
     *
     * @param  array{search?: string, status?: string}  $filters
     */
    public function paginate(Student $student, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? '');

        return $this->base($student)
            // A coluna `data` é text com o JSON da notificação: o LIKE cobre
            // título e corpo de uma vez (busca simples, portável p/ SQLite).
            ->when($search !== '', fn ($query) => $query->where('data', 'LIKE', '%'.$search.'%'))
            ->when($status === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($status === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->latest()
            ->paginate($perPage, ['*'], 'mensagens');
    }

    /**
     * Totais do aluno para os indicadores da página.
     *
     * @return array{total: int, unread: int, read: int}
     */
    public function counts(Student $student): array
    {
        if (! $this->hasAppAccess($student)) {
            return ['total' => 0, 'unread' => 0, 'read' => 0];
        }

        $total = $this->base($student)->count();
        $unread = $this->base($student)->whereNull('read_at')->count();

        return ['total' => $total, 'unread' => $unread, 'read' => $total - $unread];
    }

    /**
     * Mensagens não lidas por aluno (chave = id do aluno) — usado na
     * listagem de alunos para sinalizar quem ainda não leu.
     *
     * @param  iterable<int|string>  $studentIds
     * @return array<int, int>
     */
    public function unreadCountsByStudent(iterable $studentIds): array
    {
        $userIdsByStudent = Student::query()
            ->whereIn('id', Collection::make($studentIds)->filter()->all())
            ->whereNotNull('user_id')
            ->pluck('user_id', 'id');

        if ($userIdsByStudent->isEmpty()) {
            return [];
        }

        $counts = $this->baseQuery()
            ->selectRaw('notifiable_id, count(*) as total')
            ->whereIn('notifiable_id', $userIdsByStudent->values()->all())
            ->whereNull('read_at')
            ->groupBy('notifiable_id')
            ->pluck('total', 'notifiable_id');

        return $userIdsByStudent
            ->mapWithKeys(fn ($userId, $studentId) => [(int) $studentId => (int) ($counts[$userId] ?? 0)])
            ->all();
    }

    /**
     * Notificações (mensagens) do usuário do aluno.
     */
    private function base(Student $student)
    {
        return $student->user->notifications()->where('type', TeacherMessage::class);
    }

    /**
     * Consulta crua na tabela de notificações (sem depender de um aluno).
     */
    private function baseQuery()
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', (new User)->getMorphClass())
            ->where('type', TeacherMessage::class);
    }
}
