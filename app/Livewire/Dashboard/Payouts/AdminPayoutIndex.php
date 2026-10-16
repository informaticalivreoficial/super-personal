<?php

namespace App\Livewire\Dashboard\Payouts;

use App\Enums\PayoutStatus;
use App\Models\Payout;
use App\Services\PayoutService;
use App\Traits\WithToastr;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Repasses (visão da plataforma): lista os pedidos de todas as cobranças
 * online e permite confirmar a transferência (pago) ou cancelar o repasse
 * (as cobranças voltam ao saldo do treinador).
 */
class AdminPayoutIndex extends Component
{
    use WithPagination, WithToastr;

    protected $paginationTheme = 'tailwind';

    public string $filterStatus = '';

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function markPaid($payoutId, PayoutService $service): void
    {
        $payout = Payout::find($payoutId) ?? abort(404);

        $service->markPaid($payout);

        $this->toastSuccess('Repasse marcado como pago.');
    }

    public function cancel($payoutId, PayoutService $service): void
    {
        $payout = Payout::find($payoutId) ?? abort(404);

        $service->cancel($payout);

        $this->toastSuccess('Repasse cancelado; as cobranças voltaram ao saldo do treinador.');
    }

    #[Title('Repasses')]
    public function render()
    {
        $payouts = Payout::query()
            ->with('teacher')
            ->when($this->filterStatus, fn ($query) => $query->where('status', $this->filterStatus))
            ->orderByRaw("CASE WHEN status = 'requested' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.dashboard.payouts.admin-index', [
            'payouts' => $payouts,
            'statusLabels' => PayoutStatus::labels(),
            'pendingTotal' => (float) Payout::where('status', PayoutStatus::REQUESTED)->sum('net_amount'),
            'paidTotal' => (float) Payout::where('status', PayoutStatus::PAID)->sum('net_amount'),
        ]);
    }
}
