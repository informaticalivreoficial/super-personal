<?php

namespace App\Models;

use App\Enums\PaymentChannel;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Traits\BelongsToTeacher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cobrança do aluno.
 *
 * Dois canais:
 * - manual: registro de cobrança paga por fora (sem gateway).
 * - online: paga pelo app, cai no PagBank da plataforma; a plataforma retém
 *   `fee_amount` (a receita do SaaS) e o treinador recebe `net_amount`
 *   (repassado via Payout).
 */
class Payment extends Model
{
    use BelongsToTeacher, HasFactory;

    /** Cobrança processada pelo gateway da plataforma. */
    public const GATEWAY_PAGBANK = 'pagbank';

    protected $fillable = [
        'description',
        'amount',
        'channel',
        'due_date',
        'paid_at',
        'status',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'channel' => PaymentChannel::class,
        'fee_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'status' => PaymentStatus::class,
        'payment_method' => PaymentMethod::class,
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }

    /** Cobrança feita pelo app (cai no gateway da plataforma). */
    public function isOnline(): bool
    {
        return $this->channel === PaymentChannel::ONLINE;
    }

    /** Cobrança online ainda em aberto → pode gerar pagamento no app. */
    public function isPayable(): bool
    {
        return $this->isOnline()
            && in_array($this->status, [PaymentStatus::PENDING, PaymentStatus::OVERDUE], true);
    }

    /** Cobrança online já paga e ainda não repassada ao treinador. */
    public function isAwaitingPayout(): bool
    {
        return $this->isOnline()
            && $this->status === PaymentStatus::PAID
            && $this->payout_id === null;
    }

    /** Referência enviada ao gateway (`reference_id` do pedido). */
    public function externalReference(): string
    {
        return 'pay-'.$this->id;
    }
}
