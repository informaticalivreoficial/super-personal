<?php

namespace App\Models;

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Traits\BelongsToTeacher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Assinatura do treinador COM a plataforma SaaS.
 * Não misturar com Payment (cobrança do aluno ao treinador).
 * Gateway: manual (admin) ou pagbank (checkout transparente no painel).
 */
class Subscription extends Model
{
    use BelongsToTeacher, HasFactory;

    public const GATEWAY_MANUAL = 'manual';

    public const GATEWAY_PAGBANK = 'pagbank';

    protected $fillable = [
        'plan_id',
        'period',
        'gateway',
        'payment_id',
        'status',
        'amount',
        'starts_at',
        'trial_ends_at',
        'ends_at',
        'cancelled_at',
    ];

    protected $casts = [
        'plan_id' => 'integer',
        'period' => BillingPeriod::class,
        'status' => SubscriptionStatus::class,
        'amount' => 'decimal:2',
        'starts_at' => 'date',
        'trial_ends_at' => 'date',
        'ends_at' => 'date',
        'cancelled_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SaasPlan::class, 'plan_id');
    }

    /** Considerada vigente para enforcement (ativa e não expirada). */
    public function isCurrentlyActive(): bool
    {
        if ($this->status !== SubscriptionStatus::ACTIVE) {
            return false;
        }

        return $this->ends_at === null || $this->ends_at->isFuture() || $this->ends_at->isToday();
    }

    /** Limite de alunos desta assinatura (null = ilimitado; sem plano = sem limite). */
    public function maxStudents(): ?int
    {
        return $this->plan?->max_students;
    }

    /** Referência enviada ao gateway (`reference_id` do pedido). */
    public function externalReference(): string
    {
        return 'sub-'.$this->id;
    }
}
