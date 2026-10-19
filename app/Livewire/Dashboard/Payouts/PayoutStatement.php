<?php

namespace App\Livewire\Dashboard\Payouts;

use App\Services\PayoutStatementService;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Aba "Extrato" do Financeiro: todas as movimentações do dinheiro — as
 * entradas (líquido das cobranças online pagas, com o estágio do ciclo
 * "a liberar"/"disponível"/"repassado") e as saídas (saques). Filtros de
 * período/tipo, indicadores do período e paginação.
 */
class PayoutStatement extends Component
{
    use WithPagination;

    public string $from = '';

    public string $to = '';

    public string $type = '';

    public function mount(PayoutStatementService $statements): void
    {
        $filters = $statements->filters([]);

        $this->from = $filters['from'];
        $this->to = $filters['to'];
    }

    public function updatedFrom(): void
    {
        $this->resetPage('extrato');
    }

    public function updatedTo(): void
    {
        $this->resetPage('extrato');
    }

    public function updatedType(): void
    {
        $this->resetPage('extrato');
    }

    public function applyPreset(string $preset): void
    {
        [$from, $to] = match ($preset) {
            'month' => [now()->startOfMonth(), now()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'last_30' => [now()->subDays(29), now()],
            'year' => [now()->startOfYear(), now()],
            default => [now()->startOfMonth(), now()],
        };

        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
        $this->resetPage('extrato');
    }

    public function clearFilters(): void
    {
        $this->from = Carbon::now()->startOfMonth()->toDateString();
        $this->to = Carbon::now()->toDateString();
        $this->type = '';
        $this->resetPage('extrato');
    }

    public function render(PayoutStatementService $statements)
    {
        $teacher = auth()->user()->teacher;

        $filters = $statements->filters([
            'from' => $this->from,
            'to' => $this->to,
            'type' => $this->type,
        ]);

        $entries = $statements->entries($teacher, $filters);

        return view('livewire.dashboard.payouts.statement', [
            'filters' => $filters,
            'totals' => $statements->totals($entries),
            'entries' => $statements->paginateCollection($entries),
            'typeOptions' => PayoutStatementService::TYPE_OPTIONS,
        ]);
    }
}
