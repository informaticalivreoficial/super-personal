<?php

namespace App\Livewire\Dashboard\Payouts;

use App\Services\PayoutAccountService;
use App\Services\PayoutService;
use App\Traits\WithToastr;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Aba "Saldo e saque" do Financeiro: mostra o saldo disponível (cobranças
 * online já liberadas), o que ainda está **a liberar** (dentro do prazo de
 * retenção), o saque aguardando confirmação e o formulário para pedir o
 * saque — sempre para uma das formas de recebimento cadastradas.
 */
class PayoutBalance extends Component
{
    use WithToastr;

    /** Observação opcional do pedido. */
    public string $notes = '';

    /** Conta de destino escolhida (vazio = padrão). */
    public string $accountId = '';

    public function mount(PayoutAccountService $accounts): void
    {
        $this->accountId = $this->defaultAccountId($accounts);
    }

    /** As formas de recebimento mudaram: volta o destino para a padrão. */
    #[On('payout-accounts-updated')]
    public function refreshAccount(PayoutAccountService $accounts): void
    {
        $this->accountId = $this->defaultAccountId($accounts);
    }

    public function requestPayout(PayoutService $service, PayoutAccountService $accounts): void
    {
        $this->validate([
            'notes' => ['nullable', 'string', 'max:255'],
            'accountId' => ['nullable', 'integer'],
        ], [
            'notes.max' => 'A observação pode ter no máximo 255 caracteres.',
        ]);

        $teacher = auth()->user()->teacher;

        try {
            $payout = $service->request(
                $teacher,
                $this->accountId !== '' ? (int) $this->accountId : null,
                $this->notes ?: null,
            );
        } catch (ValidationException $e) {
            $this->toastError(collect($e->errors())->flatten()->first() ?? 'Não foi possível solicitar o saque.');

            return;
        }

        $this->notes = '';
        $this->accountId = $this->defaultAccountId($accounts);

        $this->toastSuccess(
            'Saque solicitado: R$ '.number_format((float) $payout->net_amount, 2, ',', '.').'.',
        );
    }

    public function render(PayoutService $payouts, PayoutAccountService $accounts)
    {
        $teacher = auth()->user()->teacher;
        $pending = $payouts->pendingRequest($teacher);

        return view('livewire.dashboard.payouts.balance', [
            'balance' => $payouts->balance($teacher),
            'available' => $payouts->awaitingPayments($teacher),
            'releasing' => $payouts->releasingPayments($teacher),
            'releasingTotal' => $payouts->releasingTotal($teacher),
            'pending' => $pending,
            'pendingTotal' => $payouts->pendingTotal($teacher),
            'paidTotal' => $payouts->paidTotal($teacher),
            'holdDays' => $payouts->holdDays(),
            'accounts' => $accounts->accounts($teacher),
        ]);
    }

    private function defaultAccountId(PayoutAccountService $accounts): string
    {
        $teacher = auth()->user()->teacher;

        return (string) ($accounts->defaultAccount($teacher)?->id ?? '');
    }
}
