<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use App\Notifications\NewTrainingAvailable;
use App\Notifications\PaymentDueSoon;
use App\Notifications\PaymentOverdue;
use App\Notifications\TeacherMessage;
use App\Notifications\TrainingMissed;
use App\Notifications\TrainingToday;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

/**
 * Auditoria das notificações enviadas aos alunos do treinador.
 *
 * Leitura pura sobre a tabela `notifications` (canal `database`): mostra o
 * que o aluno recebeu — mensagem do treinador, plano publicado e os lembretes
 * automáticos — com filtros e situação de leitura.
 *
 * Isolamento: as notificações são entregues ao *usuário* do aluno, então a
 * consulta sempre parte dos alunos do tenant (global scope) — nunca recebe
 * `teacher_id` do request. Avisos enviados por e-mail (troca de e-mail) não
 * entram aqui: não passam pelo canal database.
 */
class StudentNotificationService
{
    /**
     * Tipos relevantes para o treinador (classe => rótulo).
     *
     * @var array<class-string, string>
     */
    public const TYPES = [
        TeacherMessage::class => 'Mensagem do treinador',
        NewTrainingAvailable::class => 'Novo treino disponível',
        PaymentDueSoon::class => 'Cobrança a vencer',
        PaymentOverdue::class => 'Cobrança em atraso',
        TrainingToday::class => 'Treino de hoje',
        TrainingMissed::class => 'Treino não realizado',
    ];

    /** Filtros de situação de leitura (mesmo padrão dos repasses). */
    public const STATUS_OPTIONS = [
        '' => 'Todas',
        'unread' => 'Não lidas',
        'read' => 'Lidas',
    ];

    /**
     * Normaliza os filtros: tipo fora da lista é ignorado, aluno não numérico
     * também, e datas invertidas são trocadas de lugar.
     *
     * @param  array<string, mixed>  $input
     * @return array{search: string, student_id: int|null, type: string|null, status: string, from: string|null, to: string|null}
     */
    public function filters(array $input): array
    {
        $from = $this->parseDate($input['from'] ?? null);
        $to = $this->parseDate($input['to'] ?? null);

        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $studentId = (int) ($input['student_id'] ?? 0);
        $type = (string) ($input['type'] ?? '');
        $status = (string) ($input['status'] ?? '');

        return [
            'search' => trim((string) ($input['search'] ?? '')),
            'student_id' => $studentId > 0 ? $studentId : null,
            'type' => array_key_exists($type, self::TYPES) ? $type : null,
            'status' => array_key_exists($status, self::STATUS_OPTIONS) ? $status : '',
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * Notificações dos alunos do tenant, mais recentes primeiro.
     *
     * @param  array<string, mixed>  $filters  (já normalizados por filters())
     */
    public function query(array $filters): Builder
    {
        $userIds = $this->studentUserIds($filters['student_id'] ?? null);

        return DatabaseNotification::query()
            ->where('notifiable_type', (new User)->getMorphClass())
            ->whereIn('notifiable_id', $userIds)
            // Os caminhos JSON (`data->title`) comparam o texto já decodificado,
            // então a busca acha acento ("cobrança") mesmo com o JSON guardando
            // `\u00e7` — um LIKE direto na coluna só casaria texto sem acento.
            ->when(($filters['search'] ?? '') !== '', function ($query) use ($filters) {
                $term = '%'.$filters['search'].'%';

                $query->where(fn ($nested) => $nested
                    ->where('data->title', 'like', $term)
                    ->orWhere('data->message', 'like', $term));
            })
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when(($filters['status'] ?? '') === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when(($filters['status'] ?? '') === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('created_at', '>=', $from.' 00:00:00'))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('created_at', '<=', $to.' 23:59:59'));
    }

    /**
     * Lista paginada (mais recentes primeiro) com o aluno de cada aviso.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $notifications = $this->query($filters)
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage);

        // O aluno de cada notificação (o destinatário é o usuário dele).
        $students = Student::query()
            ->whereIn('user_id', Collection::make($notifications->items())->pluck('notifiable_id')->unique()->all())
            ->get(['id', 'user_id', 'name'])
            ->keyBy('user_id');

        $notifications->getCollection()->each(
            fn (DatabaseNotification $notification) => $notification->setAttribute('student', $students->get($notification->notifiable_id))
        );

        return $notifications;
    }

    /**
     * Indicadores do recorte atual.
     *
     * @param  array<string, mixed>  $filters
     * @return array{total: int, unread: int, read: int}
     */
    public function counts(array $filters): array
    {
        $total = $this->query($filters)->count();
        $unread = $this->query($filters)->whereNull('read_at')->count();

        return ['total' => $total, 'unread' => $unread, 'read' => $total - $unread];
    }

    /**
     * Alunos do tenant para o select (id => nome).
     *
     * @return Collection<int, string>
     */
    public function studentOptions(): Collection
    {
        return Student::query()
            ->whereNotNull('user_id')
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Rótulo do tipo (`type` guarda a classe FQCN).
     */
    public function typeLabel(string $type): string
    {
        return self::TYPES[$type] ?? 'Aviso';
    }

    /**
     * Avisos por tipo no recorte (para o resumo) — ordem da lista oficial.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array{type: string, label: string, count: int}>
     */
    public function typeCounts(array $filters): array
    {
        $counts = $this->query($filters)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $rows = [];

        foreach (self::TYPES as $type => $label) {
            $total = (int) ($counts[$type] ?? 0);

            if ($total > 0) {
                $rows[] = ['type' => $type, 'label' => $label, 'count' => $total];
            }
        }

        return $rows;
    }

    /**
     * Ids de usuário dos alunos do tenant (todos ou de um aluno).
     *
     * @return array<int, int>
     */
    private function studentUserIds(?int $studentId): array
    {
        return Student::query()
            ->whereNotNull('user_id')
            ->when($studentId, fn ($query, $id) => $query->where('id', $id))
            ->pluck('user_id')
            ->all();
    }

    private function parseDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
