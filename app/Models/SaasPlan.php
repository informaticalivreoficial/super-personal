<?php

namespace App\Models;

use App\Enums\BillingPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plano do catálogo do SaaS (assinatura do treinador com a plataforma).
 * Catálogo global — não tem tenant; limites valem no enforcement de alunos.
 */
class SaasPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'max_students',
        'price_monthly',
        'price_quarterly',
        'price_annual',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'max_students' => 'integer',
        'price_monthly' => 'decimal:2',
        'price_quarterly' => 'decimal:2',
        'price_annual' => 'decimal:2',
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    /** Preço do plano no período informado (0 = gratuito). */
    public function priceFor(BillingPeriod $period): float
    {
        return (float) match ($period) {
            BillingPeriod::MONTHLY => $this->price_monthly,
            BillingPeriod::QUARTERLY => $this->price_quarterly,
            BillingPeriod::ANNUAL => $this->price_annual,
        };
    }

    /** true se o plano é gratuito em todos os períodos. */
    public function isFree(): bool
    {
        return $this->price_monthly == 0
            && $this->price_quarterly == 0
            && $this->price_annual == 0;
    }

    /** Limite de alunos exibível ("Ilimitado" quando null). */
    public function maxStudentsLabel(): string
    {
        return $this->max_students === null
            ? 'Ilimitado'
            : (string) $this->max_students;
    }
}
