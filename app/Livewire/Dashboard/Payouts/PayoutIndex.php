<?php

namespace App\Livewire\Dashboard\Payouts;

use App\Enums\PayoutStatus;
use App\Services\PayoutService;
use App\Traits\WithToastr;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * "Repasses" do treinador: saldo disponível (líquido das cobranças online
 * pagas ainda sem repasse), cobranças aguardando e histórico dos repasses.
 *
 * O botão "Solicitar repasse" agrupa as cobranças em um Payout `requested`;
 * o admin confirma a transferência e marca como pago.
 */
class PayoutIndex extends Component
{
    use WithToastr;

    /** Observação opcional do pedido (ex.: chave Pix para o depósito). */
    public string $notes = '';

    public function mount(): void
    {
        // Treinador não tem perfil? 403 (o admin usa /admin/assinaturas/repasses).
        abort_unless(auth()->user()?->teacher, 403, 'Perfil de treinador não encontrado.');
    }

    public function requestPayout(PayoutService $service): void
    {
        $teacher = auth()->user()->teacher;

        try {
            $payout = $service->request($teacher, $this->notes ?: null);
        } catch (ValidationException $e) {
            $this->toastError($e->errors()['payout'][0] ?? 'Não foi possível solicitar o repasse.');

            return;
        }

        $this->notes = '';

        $this->toastSuccess(
            'Repasse solicitado: R$ '.number_format((float) $payout->net_amount, 2, ',', '.').'.',
        );
    }

    #[Title('Repasses')]
    public function render(PayoutService $service)
    {
        $teacher = auth()->user()->teacher;

        return view('livewire.dashboard.payouts.index', [
            'balance' => $service->balance($teacher),
            'awaiting' => $service->awaitingPayments($teacher),
            'payouts' => $service->payouts($teacher)->get(),
            'paidTotal' => $service->paidTotal($teacher),
            'pendingTotal' => $service->pendingTotal($teacher),
            'statusLabels' => PayoutStatus::labels(),
            'pixKey' => $teacher->pix_key,
        ]);
    }
}
