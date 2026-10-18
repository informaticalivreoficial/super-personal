<?php

namespace App\Services;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Relatório financeiro do treinador.
 *
 * Competência: tudo é apurado pela **data de vencimento** da cobrança — é o
 * que o treinador enxerga como "a mensalidade de outubro", paga ou não.
 *
 * Leitura pura sob o global scope `tenant` (nenhuma consulta recebe
 * `teacher_id`) e agregação em PHP, portável entre o SQLite dos testes e o
 * MariaDB do runtime.
 */
class FinancialReportService
{
    /** Presets de período do painel (chave => rótulo). */
    public const PERIODS = [
        'month' => 'Este mês',
        'last_month' => 'Mês passado',
        'last_30' => 'Últimos 30 dias',
        'year' => 'Este ano',
    ];

    /** Colunas usadas nas agregações (não precisa do registro inteiro). */
    private const AGGREGATE_COLUMNS = [
        'id', 'student_id', 'amount', 'fee_amount', 'net_amount',
        'channel', 'status', 'due_date', 'paid_at',
    ];

    /**
     * Normaliza os filtros do relatório. Datas invertidas são trocadas de
     * lugar; valor inválido de canal/situação é simplesmente ignorado.
     *
     * @param  array<string, mixed>  $input
     * @return array{from: string, to: string, student_id: int|null, channel: string|null, status: string|null}
     */
    public function filters(array $input): array
    {
        $from = $this->parse($input['from'] ?? null) ?? CarbonImmutable::now()->startOfMonth();
        $to = $this->parse($input['to'] ?? null) ?? CarbonImmutable::now()->endOfMonth();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $studentId = (int) ($input['student_id'] ?? 0);

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'student_id' => $studentId > 0 ? $studentId : null,
            'channel' => PaymentChannel::tryFrom((string) ($input['channel'] ?? ''))?->value,
            'status' => PaymentStatus::tryFrom((string) ($input['status'] ?? ''))?->value,
        ];
    }

    /**
     * Intervalo (`Y-m-d`) de um preset de período.
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
     * Cobranças do período sob os filtros (builder base da lista e do CSV).
     *
     * O intervalo usa o **dia inteiro** nas duas pontas: a coluna é `date`,
     * mas o driver guarda o valor com hora (ex.: "2026-10-27 00:00:00"), então
     * um limite superior só com a data deixaria o vencimento do último dia
     * fora do relatório.
     *
     * @param  array{from: string, to: string, student_id?: int|null, channel?: string|null, status?: string|null}  $filters
     */
    public function query(array $filters): Builder
    {
        return Payment::query()
            ->with('student')
            ->whereBetween('due_date', [
                $filters['from'].' 00:00:00',
                $filters['to'].' 23:59:59',
            ])
            ->when($filters['student_id'] ?? null, fn (Builder $query, $id) => $query->where('student_id', $id))
            ->when($filters['channel'] ?? null, fn (Builder $query, $channel) => $query->where('channel', $channel))
            ->when($filters['status'] ?? null, fn (Builder $query, $status) => $query->where('status', $status));
    }

    /**
     * Lista do relatório (mais recentes primeiro) — usada com `paginate()`.
     *
     * @param  array<string, mixed>  $filters
     */
    public function payments(array $filters): Builder
    {
        return $this->query($filters)->orderByDesc('due_date')->orderByDesc('id');
    }

    /**
     * KPIs do período.
     *
     * "Em atraso" considera a cobrança marcada como atrasada **ou** pendente
     * com vencimento já passado (mesma definição usada no painel).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, float|int>
     */
    public function summary(array $filters): array
    {
        $today = CarbonImmutable::now()->startOfDay();

        $summary = [
            'count' => 0,
            'count_paid' => 0,
            'count_open' => 0,
            'count_overdue' => 0,
            'count_manual' => 0,
            'count_online' => 0,
            'received' => 0.0,
            'fees' => 0.0,
            'net' => 0.0,
            'open_amount' => 0.0,
            'overdue_amount' => 0.0,
        ];

        foreach ($this->rows($filters) as $payment) {
            if ($payment->status === PaymentStatus::CANCELLED) {
                continue;
            }

            $amount = (float) $payment->amount;

            $summary['count']++;
            $summary[$payment->isOnline() ? 'count_online' : 'count_manual']++;

            if ($payment->status === PaymentStatus::PAID) {
                $summary['count_paid']++;
                $summary['received'] += $amount;
                $summary['fees'] += (float) ($payment->fee_amount ?? 0);
                $summary['net'] += (float) ($payment->net_amount ?? $amount);

                continue;
            }

            if ($this->isOverdue($payment, $today)) {
                $summary['count_overdue']++;
                $summary['overdue_amount'] += $amount;

                continue;
            }

            if ($payment->status === PaymentStatus::PENDING) {
                $summary['count_open']++;
                $summary['open_amount'] += $amount;
            }
        }

        return array_map(
            fn ($value) => is_float($value) ? round($value, 2) : $value,
            $summary
        );
    }

    /**
     * Recebido mês a mês dentro do período (só cobranças pagas).
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array{month: string, label: string, count: int, received: float, fees: float, net: float}>
     */
    public function byMonth(array $filters): array
    {
        $months = [];

        foreach ($this->rows($filters) as $payment) {
            if ($payment->status !== PaymentStatus::PAID || ! $payment->due_date) {
                continue;
            }

            $key = $payment->due_date->format('Y-m');

            $months[$key] ??= [
                'month' => $key,
                'label' => $payment->due_date->translatedFormat('F/Y'),
                'count' => 0,
                'received' => 0.0,
                'fees' => 0.0,
                'net' => 0.0,
            ];

            $amount = (float) $payment->amount;

            $months[$key]['count']++;
            $months[$key]['received'] += $amount;
            $months[$key]['fees'] += (float) ($payment->fee_amount ?? 0);
            $months[$key]['net'] += (float) ($payment->net_amount ?? $amount);
        }

        ksort($months);

        return array_values(array_map(fn (array $month) => [
            ...$month,
            'received' => round($month['received'], 2),
            'fees' => round($month['fees'], 2),
            'net' => round($month['net'], 2),
        ], $months));
    }

    /**
     * Recebido por aluno no período (ranking dos que mais geraram líquido).
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array{student_id: int|null, name: string, count: int, received: float, net: float}>
     */
    public function byStudent(array $filters, int $limit = 10): array
    {
        $students = [];

        foreach ($this->rows($filters) as $payment) {
            if ($payment->status !== PaymentStatus::PAID) {
                continue;
            }

            $key = (int) $payment->student_id;

            $students[$key] ??= [
                'student_id' => $payment->student_id,
                'name' => $payment->student?->name ?? '—',
                'count' => 0,
                'received' => 0.0,
                'net' => 0.0,
            ];

            $amount = (float) $payment->amount;

            $students[$key]['count']++;
            $students[$key]['received'] += $amount;
            $students[$key]['net'] += (float) ($payment->net_amount ?? $amount);
        }

        usort($students, fn (array $a, array $b) => $b['net'] <=> $a['net']);

        return array_map(fn (array $student) => [
            ...$student,
            'received' => round($student['received'], 2),
            'net' => round($student['net'], 2),
        ], array_slice($students, 0, $limit));
    }

    /**
     * Linhas do CSV (com cabeçalho) para exportação — gerador para não
     * carregar tudo na memória.
     *
     * @param  array<string, mixed>  $filters
     * @return iterable<int, array<int, string|float>>
     */
    public function csvRows(array $filters): iterable
    {
        yield [
            'Vencimento', 'Aluno', 'Descrição', 'Canal', 'Situação',
            'Valor', 'Taxa', 'Líquido', 'Pago em',
        ];

        $channels = PaymentChannel::labels();
        $statuses = PaymentStatus::labels();

        foreach ($this->payments($filters)->cursor() as $payment) {
            yield [
                $payment->due_date?->format('d/m/Y') ?? '',
                $payment->student?->name ?? '',
                (string) $payment->description,
                $channels[$payment->channel?->value] ?? '',
                $statuses[$payment->status?->value] ?? '',
                number_format((float) $payment->amount, 2, ',', ''),
                $payment->fee_amount !== null ? number_format((float) $payment->fee_amount, 2, ',', '') : '',
                $payment->net_amount !== null ? number_format((float) $payment->net_amount, 2, ',', '') : '',
                $payment->paid_at?->format('d/m/Y') ?? '',
            ];
        }
    }

    /**
     * Registros do período com as colunas de agregação.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Payment>
     */
    private function rows(array $filters): Collection
    {
        return $this->query($filters)->get(self::AGGREGATE_COLUMNS);
    }

    private function isOverdue(Payment $payment, CarbonImmutable $today): bool
    {
        if ($payment->status === PaymentStatus::OVERDUE) {
            return true;
        }

        return $payment->status === PaymentStatus::PENDING
            && $payment->due_date !== null
            && $payment->due_date->lt($today);
    }

    private function parse(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
