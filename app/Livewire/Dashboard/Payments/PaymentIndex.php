<?php

namespace App\Livewire\Dashboard\Payments;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Models\Config;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentIndex extends Component
{
    use WithPagination, WithToastr;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public string $filterStatus = '';

    public string $filterChannel = '';

    public $deleteId = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', Payment::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterChannel(): void
    {
        $this->resetPage();
    }

    public function confirmDelete($paymentId): void
    {
        $payment = Payment::find($paymentId) ?? abort(404);
        Gate::authorize('delete', $payment);

        $this->deleteId = $payment->id;
    }

    public function delete(PaymentService $service): void
    {
        $payment = Payment::find($this->deleteId);

        if ($payment) {
            Gate::authorize('delete', $payment);

            try {
                $service->delete($payment);
                $this->toastSuccess('Pagamento excluído com sucesso.');
            } catch (ValidationException $e) {
                $this->toastError($e->validator->errors()->first('payment'));
            }
        }

        $this->deleteId = null;
    }

    public function markAsPaid($paymentId, PaymentService $service): void
    {
        $payment = Payment::find($paymentId) ?? abort(404);
        Gate::authorize('update', $payment);

        if ($payment->isOnline()) {
            $this->toastError('Cobranças online são confirmadas automaticamente pelo gateway.');

            return;
        }

        $service->update($payment, ['status' => PaymentStatus::PAID->value]);
        $this->toastSuccess('Pagamento marcado como pago.');
    }

    #[Title('Pagamentos')]
    public function render()
    {
        $payments = Payment::query()
            ->with('student')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('description', 'LIKE', "%{$this->search}%")
                        ->orWhereHas('student', function ($sq) {
                            $sq->where('name', 'LIKE', "%{$this->search}%");
                        });
                });
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->filterChannel, function ($query) {
                $query->where('channel', $this->filterChannel);
            })
            ->orderByDesc('due_date')
            ->paginate(15);

        // KPIs do tenant autenticado (global scope `tenant` já filtra).
        $manualThisMonth = Payment::query()
            ->where('channel', PaymentChannel::MANUAL)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        // Cobranças online pagas fora do prazo de liberação = saldo do treinador.
        $releasedBefore = now()->subDays(Config::payoutHoldDays());

        $awaitingPayout = Payment::query()
            ->where('channel', PaymentChannel::ONLINE)
            ->where('status', PaymentStatus::PAID)
            ->whereNull('payout_id')
            ->where('paid_at', '<=', $releasedBefore);

        $releasing = Payment::query()
            ->where('channel', PaymentChannel::ONLINE)
            ->where('status', PaymentStatus::PAID)
            ->whereNull('payout_id')
            ->where('paid_at', '>', $releasedBefore);

        return view('livewire.dashboard.payments.index', [
            'payments' => $payments,
            'statuses' => PaymentStatus::labels(),
            'channels' => PaymentChannel::labels(),
            'manualThisMonth' => $manualThisMonth,
            'manualLimit' => PaymentService::MANUAL_MONTHLY_LIMIT,
            'awaitingPayoutCount' => (clone $awaitingPayout)->count(),
            'awaitingPayoutTotal' => (float) (clone $awaitingPayout)->sum('net_amount'),
            'releasingTotal' => (float) $releasing->sum('net_amount'),
            'holdDays' => Config::payoutHoldDays(),
        ]);
    }
}
