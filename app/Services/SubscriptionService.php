<?php

namespace App\Services;

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

/**
 * Assinatura do treinador COM a plataforma SaaS.
 * Não misturar com PaymentService (cobrança do aluno ao treinador).
 *
 * Caminho único de escrita: manual (admin), gratuito automático
 * ou via Mercado Pago (Checkout Pro + webhook).
 */
class SubscriptionService
{
    public function __construct(private MercadoPagoService $mercadopago) {}

    /**
     * Atribui o plano gratuito ao treinador (idempotente).
     * Chamado no cadastro de professor; não rebaixa quem já tem plano.
     */
    public function assignFreePlan(Teacher $teacher): ?Subscription
    {
        // Consulta direta (sem memoizar relation): chamadas repetidas no mesmo
        // request/instância não podem gerar duplicata.
        if ($existing = $teacher->subscription()->first()) {
            return $existing;
        }

        $freePlan = $this->freePlan();

        if (! $freePlan) {
            return null;
        }

        return DB::transaction(function () use ($teacher, $freePlan) {
            $subscription = new Subscription;
            $subscription->teacher_id = $teacher->id;
            $subscription->plan_id = $freePlan->id;
            $subscription->period = BillingPeriod::MONTHLY;
            $subscription->gateway = Subscription::GATEWAY_MANUAL;
            $subscription->status = SubscriptionStatus::ACTIVE;
            $subscription->amount = 0;
            $subscription->starts_at = now()->toDateString();
            $subscription->ends_at = null;
            $subscription->save();

            return $subscription;
        });
    }

    /**
     * Troca o plano do treinador (self-service no painel).
     * Plano pago → status PENDING + preferência Mercado Pago (retorna URL do checkout);
     * plano gratuito → ativa na hora (sem gateway).
     */
    public function subscribe(Teacher $teacher, SaasPlan $plan, BillingPeriod $period): array
    {
        $amount = $plan->priceFor($period);

        $subscription = $teacher->subscription ?? new Subscription;
        $subscription->teacher_id = $teacher->id;
        $subscription->plan_id = $plan->id;
        $subscription->period = $period;
        $subscription->amount = $amount;
        $subscription->cancelled_at = null;

        if ($plan->isFree()) {
            $subscription->gateway = Subscription::GATEWAY_MANUAL;
            $subscription->status = SubscriptionStatus::ACTIVE;
            $subscription->preference_id = null;
            $subscription->payment_id = null;
            $subscription->starts_at = now()->toDateString();
            $subscription->ends_at = null;
            $subscription->save();

            return ['subscription' => $subscription, 'checkout_url' => null];
        }

        $subscription->gateway = Subscription::GATEWAY_MERCADOPAGO;
        $subscription->status = SubscriptionStatus::PENDING;
        $subscription->starts_at = now()->toDateString();
        $subscription->ends_at = null;
        $subscription->save();

        $checkoutUrl = null;

        if ($this->mercadopago->isConfigured()) {
            $checkoutUrl = $this->mercadopago->createCheckoutPreference($subscription);
            $subscription->save();
        }

        return ['subscription' => $subscription, 'checkout_url' => $checkoutUrl];
    }

    /**
     * Webhook do Mercado Pago: pagamento aprovado → assinatura ativa
     * com vigência = data do pagamento + meses do período.
     * Referência inesperada (não nossa) é ignorada.
     */
    public function activateFromPayment(array $payment): bool
    {
        $subscription = $this->findByExternalReference($payment['external_reference'] ?? null);

        if (! $subscription) {
            return false;
        }

        $subscription->status = SubscriptionStatus::ACTIVE;
        $subscription->payment_id = (string) ($payment['id'] ?? $subscription->payment_id);
        $subscription->cancelled_at = null;
        $subscription->starts_at = now()->toDateString();

        if ($subscription->period) {
            $subscription->ends_at = now()->addMonthsNoOverflow($subscription->period->months())->toDateString();
        }

        $subscription->save();

        return true;
    }

    /** Pagamento rejeitado/cancelado volta para "em atraso" (admin pode corrigir). */
    public function markPaymentRejected(array $payment): bool
    {
        $subscription = $this->findByExternalReference($payment['external_reference'] ?? null);

        if (! $subscription) {
            return false;
        }

        $subscription->status = SubscriptionStatus::PAST_DUE;
        $subscription->payment_id = (string) ($payment['id'] ?? $subscription->payment_id);
        $subscription->save();

        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(Teacher $teacher, array $data): Subscription
    {
        $subscription = new Subscription;
        $subscription->teacher_id = $teacher->id;
        $subscription->fill($data);
        $this->applyCancelledAt($subscription);
        $subscription->save();

        return $subscription;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Subscription $subscription, array $data): Subscription
    {
        $subscription->fill($data);
        $this->applyCancelledAt($subscription);
        $subscription->save();

        return $subscription;
    }

    private function findByExternalReference(?string $externalReference): ?Subscription
    {
        if (! $externalReference || ! str_starts_with($externalReference, 'sub-')) {
            return null;
        }

        return Subscription::find((int) substr($externalReference, 4));
    }

    /** Catálogo: primeiro plano gratuito ativo (slug "gratuito" ou preço zero). */
    private function freePlan(): ?SaasPlan
    {
        return SaasPlan::query()
            ->where('active', true)
            ->where('slug', 'gratuito')
            ->first()
            ?? SaasPlan::query()
                ->where('active', true)
                ->where('price_monthly', 0)
                ->orderBy('sort_order')
                ->first();
    }

    /** Rastro manual de billing: cancelada → registra o momento. */
    private function applyCancelledAt(Subscription $subscription): void
    {
        if ($subscription->status === SubscriptionStatus::CANCELLED && ! $subscription->cancelled_at) {
            $subscription->cancelled_at = now();
        }
    }
}
