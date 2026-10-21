<?php

namespace App\Services;

use App\Enums\TrainingPlanStatus;
use App\Enums\TrainingSessionStatus;
use App\Models\Sport;
use App\Models\TrainingSession;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Relatório de aderência do treinador: quanto do treino prescrito foi
 * realmente executado, por aluno e por modalidade, num recorte de período.
 *
 * Competência = `scheduled_date` da sessão. Só entram sessões de planos
 * **publicados** (`training_plans.status = active`) — rascunhos e modelos não
 * fazem parte do que o aluno recebeu. Leitura pura (sem escrita): o global
 * scope `tenant` isola o relatório do treinador logado.
 */
class AdherenceReportService
{
    public const PERIODS = [
        'month' => 'Este mês',
        'last_month' => 'Mês passado',
        'last_30' => 'Últimos 30 dias',
        'year' => 'Este ano',
    ];

    /** Colunas usadas nas agregações (o relatório não precisa do registro inteiro). */
    private const COLUMNS = [
        'id', 'student_id', 'sport_id', 'title', 'scheduled_date',
        'status', 'estimated_duration', 'distance',
    ];

    /**
     * Normaliza os filtros (datas invertidas trocadas; aluno/modalidade
     * inexistentes são ignorados).
     *
     * @param  array<string, mixed>  $input
     * @return array{from: string, to: string, student_id: int|null, sport_id: int|null}
     */
    public function filters(array $input): array
    {
        $from = $this->parse($input['from'] ?? null) ?? CarbonImmutable::now()->startOfMonth();
        $to = $this->parse($input['to'] ?? null) ?? CarbonImmutable::now()->endOfMonth();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $studentId = (int) ($input['student_id'] ?? 0);
        $sportId = (int) ($input['sport_id'] ?? 0);

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'student_id' => $studentId > 0 ? $studentId : null,
            'sport_id' => $sportId > 0 ? $sportId : null,
        ];
    }

    /**
     * Intervalo de um preset de período.
     *
     * @return array{from: string, to: string}
     */
    public function periodRange(string $period): array
    {
        $now = CarbonImmutable::now();

        [$from, $to] = match ($period) {
            'last_month' => [
                $now->subMonthNoOverflow()->startOfMonth(),
                $now->subMonthNoOverflow()->endOfMonth(),
            ],
            'last_30' => [$now->subDays(29)->startOfDay(), $now->endOfDay()],
            'year' => [$now->startOfYear(), $now->endOfYear()],
            default => [$now->startOfMonth(), $now->endOfMonth()],
        };

        return ['from' => $from->toDateString(), 'to' => $to->toDateString()];
    }

    /**
     * Sessões do recorte (builder base da tabela, do CSV e das agregações).
     *
     * O intervalo usa o dia inteiro nas duas pontas — o driver guarda a data
     * com hora e um limite superior só com a data deixaria o último dia fora.
     *
     * @param  array{from: string, to: string, student_id?: int|null, sport_id?: int|null}  $filters
     */
    public function query(array $filters): Builder
    {
        return TrainingSession::query()
            ->with(['student', 'sport'])
            ->whereHas('week.plan', fn (Builder $query) => $query->where('status', TrainingPlanStatus::ACTIVE))
            ->whereBetween('scheduled_date', [
                $filters['from'].' 00:00:00',
                $filters['to'].' 23:59:59',
            ])
            ->when($filters['student_id'] ?? null, fn (Builder $query, $id) => $query->where('student_id', $id))
            ->when($filters['sport_id'] ?? null, fn (Builder $query, $id) => $query->where('sport_id', $id));
    }

    /**
     * Sessões em memória (o relatório agrega em PHP — portável entre SQLite
     * dos testes e MariaDB do runtime).
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, TrainingSession>
     */
    public function sessions(array $filters): Collection
    {
        return $this->query($filters)
            ->orderByDesc('scheduled_date')
            ->orderBy('id')
            ->get(self::COLUMNS);
    }

    /**
     * Indicadores do período.
     *
     * `due` = sessões que já "venceram" (concluídas, puladas ou planejadas com
     * data no passado) e é a base da aderência. Planejadas de hoje em diante e
     * canceladas ficam fora do denominador.
     *
     * @param  Collection<int, TrainingSession>  $sessions
     * @return array{total: int, completed: int, skipped: int, missed: int, cancelled: int, planned: int, due: int, adherence: int|null, distance: int, duration: int}
     */
    public function summary(Collection $sessions): array
    {
        $today = CarbonImmutable::now()->startOfDay();

        $completed = $sessions->where('status', TrainingSessionStatus::COMPLETED)->count();
        $skipped = $sessions->where('status', TrainingSessionStatus::SKIPPED)->count();
        $cancelled = $sessions->where('status', TrainingSessionStatus::CANCELLED)->count();
        $planned = $sessions->where('status', TrainingSessionStatus::PLANNED);

        $missed = $planned->filter(fn (TrainingSession $session) => $session->scheduled_date->lt($today))->count();
        $upcoming = $planned->count() - $missed;
        $due = $completed + $skipped + $missed;

        $done = $sessions->where('status', TrainingSessionStatus::COMPLETED);

        return [
            'total' => $sessions->count(),
            'completed' => $completed,
            'skipped' => $skipped,
            'missed' => $missed,
            'cancelled' => $cancelled,
            'planned' => $upcoming,
            'due' => $due,
            'adherence' => $due > 0 ? (int) round($completed / $due * 100) : null,
            'distance' => (int) $done->sum('distance'),
            'duration' => (int) $done->sum('estimated_duration'),
        ];
    }

    /**
     * Aderência por aluno (ranking do maior volume para o menor).
     *
     * @param  Collection<int, TrainingSession>  $sessions
     * @return Collection<int, array{student: mixed, total: int, completed: int, skipped: int, missed: int, adherence: int|null}>
     */
    public function byStudent(Collection $sessions): Collection
    {
        return $sessions
            ->groupBy('student_id')
            ->map(function (Collection $group) {
                $summary = $this->summary($group);

                return [
                    'student' => $group->first()->student,
                    'total' => $summary['total'],
                    'completed' => $summary['completed'],
                    'skipped' => $summary['skipped'],
                    'missed' => $summary['missed'],
                    'adherence' => $summary['adherence'],
                ];
            })
            ->sortBy([['total', 'desc'], ['completed', 'desc']])
            ->values();
    }

    /**
     * Aderência por modalidade.
     *
     * @param  Collection<int, TrainingSession>  $sessions
     * @return Collection<int, array{sport: mixed, total: int, completed: int, adherence: int|null}>
     */
    public function bySport(Collection $sessions): Collection
    {
        return $sessions
            ->groupBy('sport_id')
            ->map(function (Collection $group) {
                $summary = $this->summary($group);

                return [
                    'sport' => $group->first()->sport,
                    'total' => $summary['total'],
                    'completed' => $summary['completed'],
                    'adherence' => $summary['adherence'],
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    /**
     * Opções de modalidade para o filtro (catálogo global, somente leitura).
     *
     * @return Collection<int, Sport>
     */
    public function sportOptions(): Collection
    {
        return Sport::query()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Linhas do CSV: uma por aluno (+ linha de total).
     *
     * @param  array<string, mixed>  $filters
     * @return iterable<int, array<int, string|int>>
     */
    public function csvRows(array $filters): iterable
    {
        $sessions = $this->sessions($filters);
        $summary = $this->summary($sessions);

        yield ['Aluno', 'Sessões', 'Concluídas', 'Puladas', 'Não realizadas', 'Aderência (%)'];

        foreach ($this->byStudent($sessions) as $row) {
            yield [
                $row['student']?->name ?? '—',
                (string) $row['total'],
                (string) $row['completed'],
                (string) $row['skipped'],
                (string) $row['missed'],
                $row['adherence'] === null ? '—' : (string) $row['adherence'],
            ];
        }

        yield [
            'Total',
            (string) $summary['total'],
            (string) $summary['completed'],
            (string) $summary['skipped'],
            (string) $summary['missed'],
            $summary['adherence'] === null ? '—' : (string) $summary['adherence'],
        ];
    }

    private function parse(?string $value): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', (string) $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
