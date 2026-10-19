<?php

namespace App\Services;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Models\Config;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Teacher;
use App\Models\TeacherPayoutAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Repasse do líquido das cobranças **online** ao treinador.
 *
 * O líquido de cada cobrança paga fica **retido por alguns dias** (`config.
 * payout_hold_days`, padrão 7) antes de entrar no **saldo disponível** — a
 * janela de segurança contra estorno/contestamento. Depois disso, fica
 * "disponível" e pode virar um saque: solicitar agrupa todas as cobranças
 * liberadas em um `Payout` (status `requested`) com destino (conta/chave)
 * congelado; o admin confirma a transferência e marca como pago; cancelar
 * devolve as cobranças ao saldo.
 *
 * Só existe **um saque pendente por vez**, e sempre há de existir uma conta
 * de recebimento cadastrada. Caminho único de escrita (painel e API).
 */
class PayoutService
{
    /** Prazo (dias) que o líquido fica retido antes de liberar para saque. */
    public function holdDays(): int
    {
        return Config::payoutHoldDays();
    }

    /** Data-limite: pagos até ela já liberaram; depois dela ainda retêm. */
    public function releaseThreshold(): Carbon
    {
        return now()->subDays($this->holdDays());
    }

    /** Cobranças online pagas, liberadas e ainda sem repasse (saldo sacável). */
    public function awaitingPayments(Teacher $teacher): Collection
    {
        return $this->openPayments($teacher)
            ->where('paid_at', '<=', $this->releaseThreshold())
            ->orderBy('paid_at')
            ->get();
    }

    /** Cobranças online pagas dentro do prazo de retenção (a liberar). */
    public function releasingPayments(Teacher $teacher): Collection
    {
        return $this->openPayments($teacher)
            ->where('paid_at', '>', $this->releaseThreshold())
            ->orderBy('paid_at')
            ->get();
    }

    /** Saldo disponível para saque (líquido das cobranças já liberadas). */
    public function balance(Teacher $teacher): float
    {
        return round((float) $this->awaitingPayments($teacher)->sum('net_amount'), 2);
    }

    /** Total ainda retido (a liberar após o prazo). */
    public function releasingTotal(Teacher $teacher): float
    {
        return round((float) $this->releasingPayments($teacher)->sum('net_amount'), 2);
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

    /** Saque aguardando confirmação do admin (se houver). */
    public function pendingRequest(Teacher $teacher): ?Payout
    {
        return $this->payouts($teacher)
            ->where('status', PayoutStatus::REQUESTED)
            ->first();
    }

    /**
     * Cria o saque das cobranças já liberadas e as vincula a ele.
     *
     * @throws ValidationException sem conta cadastrada, sem saldo ou já existindo saque pendente
     */
    public function request(Teacher $teacher, ?int $accountId = null, ?string $notes = null): Payout
    {
        return DB::transaction(function () use ($teacher, $accountId, $notes) {
            $account = $this->resolveAccount($teacher, $accountId);

            $payments = $this->awaitingPayments($teacher);

            if ($payments->isEmpty()) {
                throw ValidationException::withMessages([
                    'payout' => 'Não há saldo disponível para saque no momento.',
                ]);
            }

            if ($this->pendingRequest($teacher) !== null) {
                throw ValidationException::withMessages([
                    'payout' => 'Você já tem um saque aguardando confirmação. Aguarde a conclusão antes de solicitar outro.',
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
            $payout->payout_account_id = $account->id;
            $payout->destination = $account->description();
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

    /** Cobranças online pagas ainda sem repasse (liberadas ou não). */
    private function openPayments(Teacher $teacher): Builder
    {
        return Payment::withoutGlobalScope('tenant')
            ->where('teacher_id', $teacher->id)
            ->where('channel', PaymentChannel::ONLINE)
            ->where('status', PaymentStatus::PAID)
            ->whereNull('payout_id')
            ->whereNotNull('paid_at');
    }

    /**
     * Conta de destino do saque: a informada (validando o dono) ou a padrão
     * do treinador. Sem nenhuma cadastrada, bloqueia o pedido.
     *
     * @throws ValidationException
     */
    private function resolveAccount(Teacher $teacher, ?int $accountId): TeacherPayoutAccount
    {
        $account = $accountId !== null
            ? $teacher->payoutAccounts()->whereKey($accountId)->first()
            : $teacher->defaultPayoutAccount();

        if ($account === null) {
            throw ValidationException::withMessages([
                'payout_account' => 'Cadastre uma chave Pix ou conta bancária para receber antes de solicitar o saque.',
            ]);
        }

        return $account;
    }
}
