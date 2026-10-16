<?php

namespace App\Models;

use App\Enums\PayoutStatus;
use App\Traits\BelongsToTeacher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Repasse da plataforma ao treinador (líquido das cobranças online).
 *
 * O saldo disponível do treinador é a soma de `net_amount` das cobranças
 * online pagas e ainda sem `payout_id`; ao criar um repasse, elas são
 * vinculadas e saem do saldo.
 */
class Payout extends Model
{
    use BelongsToTeacher, HasFactory;

    protected $fillable = [
        'amount',
        'fee_total',
        'net_amount',
        'status',
        'gateway',
        'gateway_transfer_id',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee_total' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'status' => PayoutStatus::class,
        'paid_at' => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isPaid(): bool
    {
        return $this->status === PayoutStatus::PAID;
    }
}
