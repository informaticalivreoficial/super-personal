<?php

namespace App\Livewire\Dashboard\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentIndex extends Component
{
    use WithPagination, WithToastr;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public string $filterStatus = '';

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
            $service->delete($payment);
            $this->toastSuccess('Pagamento excluído com sucesso.');
        }

        $this->deleteId = null;
    }

    public function markAsPaid($paymentId, PaymentService $service): void
    {
        $payment = Payment::find($paymentId) ?? abort(404);
        Gate::authorize('update', $payment);

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
            ->orderByDesc('due_date')
            ->paginate(15);

        return view('livewire.dashboard.payments.index', [
            'payments' => $payments,
            'statuses' => PaymentStatus::labels(),
        ]);
    }
}
