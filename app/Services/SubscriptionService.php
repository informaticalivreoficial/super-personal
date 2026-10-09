<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\Teacher;

/**
 * Assinatura do treinador COM a plataforma SaaS (billing manual, sem gateway).
 * Não misturar com PaymentService (cobrança do aluno ao treinador).
 */
class SubscriptionService
{
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

    /** Rastro manual de billing: cancelada → registra o momento. */
    private function applyCancelledAt(Subscription $subscription): void
    {
        if ($subscription->status === SubscriptionStatus::CANCELLED && ! $subscription->cancelled_at) {
            $subscription->cancelled_at = now();
        }
    }
}
