<?php

namespace App\Services;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Models\Config;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Teacher;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Extrato financeiro do treinador (área Financeiro > Extrato).
 *
 * Leitura pura: **entradas** = líquido (`net_amount`) das cobranças online
 * pagas; **saídas** = saques solicitados/pagos. O status de cada entrada
 * reflete o ciclo do dinheiro — "A liberar" (dentro do prazo de retenção),
 * "Disponível" (já sacável) ou "Repassado" (já dentro de um saque).
 */
class PayoutStatementService
{
    public const TYPE_OPTIONS = [
        '' => 'Todas as movimentações',
        'credit' => 'Entradas',
        'debit' => 'Saídas',
    ];

    /**
     * Normaliza os filtros do extrato. Datas invertidas são trocadas;
     * período padrão = mês corrente. Tipo inválido é ignorado.
     *
     * @param  array<string, mixed>  $input
     * @return array{from: string, to: string, type: string}
     */
    public function filters(array $input = []): array
    {
        $from = $this->parseDate($input['from'] ?? null) ?? now()->startOfMonth()->toDateString();
        $to = $this->parseDate($input['to'] ?? null) ?? now()->toDateString();

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $type = (string) ($input['type'] ?? '');

        if (! array_key_exists($type, self::TYPE_OPTIONS)) {
            $type = '';
        }

        return ['from' => $from, 'to' => $to, 'type' => $type];
    }

    /** Entradas + saídas do período, mais recentes primeiro. */
    public function entries(Teacher $teacher, array $filters): Collection
    {
        $filters = $this->filters($filters);

        $entries = collect();

        if ($filters['type'] !== 'debit') {
            $entries = $entries->merge($this->creditEntries($teacher, $filters['from'], $filters['to']));
        }

        if ($filters['type'] !== 'credit') {
            $entries = $entries->merge($this->debitEntries($teacher, $filters['from'], $filters['to']));
        }

        return $entries->sortByDesc('occurred_at')->values();
    }

    /** Totais do período (para os indicadores do extrato). */
    public function totals(Collection $entries): array
    {
        $credits = round((float) $entries->where('kind', 'credit')->sum('amount'), 2);
        $debits = round((float) $entries->where('kind', 'debit')->sum('amount'), 2);

        return [
            'credits' => $credits,
            'debits' => $debits,
            'net' => round($credits - $debits, 2),
        ];
    }

    /** Extrato paginado (página vinda do componente Livewire/request). */
    public function paginate(Teacher $teacher, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginateCollection($this->entries($teacher, $filters), $perPage);
    }

    /** Pagina uma coleção de lançamentos já montada (evita refazer as queries). */
    public function paginateCollection(Collection $entries, int $perPage = 15): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            $entries->forPage(Paginator::resolveCurrentPage('extrato'), $perPage)->values(),
            $entries->count(),
            $perPage,
            Paginator::resolveCurrentPage('extrato'),
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'extrato'],
        );
    }

    /** Líquido das cobranças online pagas no período. */
    private function creditEntries(Teacher $teacher, string $from, string $to): Collection
    {
        $threshold = now()->subDays(Config::payoutHoldDays());

        return Payment::withoutGlobalScope('tenant')
            ->where('teacher_id', $teacher->id)
            ->where('channel', PaymentChannel::ONLINE)
            ->where('status', PaymentStatus::PAID)
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->with('student')
            ->orderBy('paid_at')
            ->get()
            ->map(function (Payment $payment) use ($threshold) {
                $status = $this->creditStatus($payment, $threshold);

                return [
                    'occurred_at' => $payment->paid_at,
                    'kind' => 'credit',
                    'title' => 'Cobrança online'.($payment->student?->name ? ' · '.$payment->student->name : ''),
                    'description' => $payment->description,
                    'amount' => round((float) ($payment->net_amount ?? $payment->amount), 2),
                    'status' => $status,
                    'status_label' => $this->creditStatusLabel($status),
                    'tone' => $this->creditStatusTone($status),
                ];
            });
    }

    /** Saques solicitados/pagos no período (cancelados ficam fora). */
    private function debitEntries(Teacher $teacher, string $from, string $to): Collection
    {
        return Payout::where('teacher_id', $teacher->id)
            ->whereIn('status', [PayoutStatus::REQUESTED, PayoutStatus::PAID])
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->orderBy('created_at')
            ->get()
            ->map(function (Payout $payout) {
                $paid = $payout->status === PayoutStatus::PAID;

                return [
                    'occurred_at' => $paid && $payout->paid_at ? $payout->paid_at : $payout->created_at,
                    'kind' => 'debit',
                    'title' => 'Saque',
                    'description' => $payout->destination,
                    'amount' => round((float) $payout->net_amount, 2),
                    'status' => $paid ? 'paid_out' : 'requested',
                    'status_label' => $paid ? 'Pago' : 'Aguardando',
                    'tone' => $paid ? 'neutral' : 'warning',
                ];
            });
    }

    /** Ciclo do dinheiro da entrada: a liberar → disponível → repassado. */
    private function creditStatus(Payment $payment, Carbon $threshold): string
    {
        if ($payment->payout_id !== null) {
            return 'paid_out';
        }

        return $payment->paid_at !== null && $payment->paid_at->gt($threshold)
            ? 'releasing'
            : 'available';
    }

    private function creditStatusLabel(string $status): string
    {
        return [
            'releasing' => 'A liberar',
            'available' => 'Disponível',
            'paid_out' => 'Repassado',
        ][$status] ?? $status;
    }

    private function creditStatusTone(string $status): string
    {
        return [
            'releasing' => 'warning',
            'available' => 'success',
            'paid_out' => 'neutral',
        ][$status] ?? 'neutral';
    }

    private function parseDate(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Exception) {
            return null;
        }
    }
}
