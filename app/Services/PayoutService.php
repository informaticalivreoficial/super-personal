<?php

namespace App\Services;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Repasse do líquido das cobranças **online** ao treinador.
 *
 * O saldo disponível é a soma de `net_amount` das cobranças online pagas e
 * ainda sem `payout_id`. Solicitar um repasse agrupa essas cobranças em um
 * `Payout` (status `requested`); o admin confirma a transferência e marca
 * como pago; cancelar devolve as cobranças ao saldo.
 *
 * Caminho único de escrita (painel e futuro saque automático).
 */
class PayoutService
{
    /** Cobranças online pagas e ainda sem repasse (ordem cronológica). */
    public function awaitingPayments(Teacher $teacher): Collection
    {
        return Payment::withoutGlobalScope('tenant')
            ->where('teacher_id', $teacher->id)
            ->where('channel', PaymentChannel::ONLINE)
            ->where('status', PaymentStatus::PAID)
            ->whereNull('payout_id')
            ->orderBy('paid_at')
            ->get();
    }

    /** Saldo disponível para repasse (líquido das cobranças em aberto). */
    public function balance(Teacher $teacher): float
    {
        return round((float) $this->awaitingPayments($teacher)->sum('net_amount'), 2);
    }

    /** Total já repassado (repasses pagos). */
    public function paidTotal(Teacher $teacher): float
    {
        return round((float) $this->payouts($teacher)
            ->where('status', PayoutStatus::PAID)
            ->sum('net_amount'), 2);
    }

    /** Total solicitado e ainda não pago. */
    public function pendingTotal(Teacher $teacher): float
    {
        return round((float) $this->payouts($teacher)
            ->where('status', PayoutStatus::REQUESTED)
            ->sum('net_amount'), 2);
    }

    /**
     * Cria o repasse das cobranças em aberto e as vincula a ele.
     *
     * @throws ValidationException quando não há saldo disponível
     */
    public function request(Teacher $teacher, ?string $notes = null): Payout
    {
        return DB::transaction(function () use ($teacher, $notes) {
            $payments = $this->awaitingPayments($teacher);

            if ($payments->isEmpty()) {
                throw ValidationException::withMessages([
                    'payout' => 'Não há saldo disponível para repasse no momento.',
                ]);
            }

            $gross = round((float) $payments->sum('amount'), 2);
            $fees = round((float) $payments->sum('fee_amount'), 2);
            $net = round((float) $payments->sum('net_amount'), 2);

            $payout = new Payout;
            // `teacher_id` fora do fillable: sempre setado pelo Service.
            $payout->teacher_id = $teacher->id;
            $payout->amount = $gross;
            $payout->fee_total = $fees;
            $payout->net_amount = $net;
            $payout->status = PayoutStatus::REQUESTED;
            $payout->notes = $notes;
            $payout->save();

            Payment::withoutGlobalScope('tenant')
                ->whereIn('id', $payments->pluck('id'))
                ->update(['payout_id' => $payout->id]);

            return $payout;
        });
    }

    /** Confirma a transferência do repasse (admin). */
    public function markPaid(Payout $payout, ?string $notes = null): Payout
    {
        $payout->status = PayoutStatus::PAID;
        $payout->paid_at = now();

        if ($notes !== null) {
            $payout->notes = $notes;
        }

        $payout->save();

        return $payout;
    }

    /** Cancela o repasse e devolve as cobranças ao saldo. */
    public function cancel(Payout $payout): Payout
    {
        $payout->status = PayoutStatus::CANCELLED;
        $payout->paid_at = null;
        $payout->save();

        Payment::withoutGlobalScope('tenant')
            ->where('payout_id', $payout->id)
            ->update(['payout_id' => null]);

        return $payout;
    }

    /** Repasses do treinador (mais recentes primeiro). */
    public function payouts(Teacher $teacher)
    {
        return Payout::where('teacher_id', $teacher->id)->orderByDesc('created_at');
    }
}
