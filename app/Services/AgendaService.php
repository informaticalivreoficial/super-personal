<?php

namespace App\Services;

use App\Enums\TrainingSessionStatus;
use App\Models\TrainingSession;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Agenda semanal do treinador: recorte de segunda a domingo com todos os
 * treinos do tenant, agrupados por dia e com os totais do período.
 *
 * Leitura pura — o isolamento por tenant vem do global scope do
 * TrainingSession (App\Traits\BelongsToTeacher); a view não faz query.
 */
class AgendaService
{
    /**
     * Semana do treinador já montada (7 dias) + totais do recorte.
     *
     * @param  array{student_id?: int|string|null, sport_id?: int|string|null, status?: string|null}  $filters
     * @return array{
     *     days: Collection<int, array{date: CarbonInterface, is_today: bool, is_past: bool, sessions: Collection<int, TrainingSession>}>,
     *     totals: array<string, int>
     * }
     */
    public function week(CarbonInterface $startOfWeek, array $filters = []): array
    {
        $start = $startOfWeek->copy()->startOfDay();
        $end = $start->copy()->addDays(6)->endOfDay();

        $sessions = TrainingSession::query()
            ->with(['student', 'sport', 'week'])
            ->whereBetween('scheduled_date', [$start->toDateString(), $end->toDateString()])
            ->when($filters['student_id'] ?? null, fn ($query, $studentId) => $query->where('student_id', $studentId))
            ->when($filters['sport_id'] ?? null, fn ($query, $sportId) => $query->where('sport_id', $sportId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('scheduled_date')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // Os 7 dias da semana sempre aparecem (agenda), mesmo sem treinos.
        $days = collect(range(0, 6))->map(function (int $offset) use ($start, $sessions): array {
            $date = $start->copy()->addDays($offset);

            return [
                'date' => $date,
                'is_today' => $date->isToday(),
                'is_past' => $date->isPast() && ! $date->isToday(),
                'sessions' => $sessions
                    ->filter(fn (TrainingSession $session) => $session->scheduled_date?->toDateString() === $date->toDateString())
                    ->values(),
            ];
        });

        return [
            'days' => $days,
            'totals' => [
                'sessions' => $sessions->count(),
                'students' => $sessions->pluck('student_id')->unique()->count(),
                'completed' => $this->countByStatus($sessions, TrainingSessionStatus::COMPLETED),
                'planned' => $this->countByStatus($sessions, TrainingSessionStatus::PLANNED),
                'skipped' => $this->countByStatus($sessions, TrainingSessionStatus::SKIPPED),
                'cancelled' => $this->countByStatus($sessions, TrainingSessionStatus::CANCELLED),
                'duration_seconds' => (int) $sessions->sum('estimated_duration'),
                'distance_meters' => (int) $sessions->sum('distance'),
            ],
        ];
    }

    /**
     * @param  Collection<int, TrainingSession>  $sessions
     */
    private function countByStatus(Collection $sessions, TrainingSessionStatus $status): int
    {
        return $sessions->filter(fn (TrainingSession $session) => $session->status === $status)->count();
    }
}
