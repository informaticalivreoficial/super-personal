<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Traits\BelongsToTeacher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Assinatura do professor COM a plataforma SaaS.
 * Não misturar com Payment (cobrança do aluno ao professor).
 * Estrutura preparada — regras de billing virão numa fase futura.
 */
class Subscription extends Model
{
    use BelongsToTeacher, HasFactory;

    protected $fillable = [
        'plan',
        'status',
        'amount',
        'starts_at',
        'trial_ends_at',
        'ends_at',
        'cancelled_at',
    ];

    protected $casts = [
        'status' => SubscriptionStatus::class,
        'amount' => 'decimal:2',
        'starts_at' => 'date',
        'trial_ends_at' => 'date',
        'ends_at' => 'date',
        'cancelled_at' => 'datetime',
    ];
}
