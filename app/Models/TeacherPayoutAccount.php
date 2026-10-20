<?php

namespace App\Models;

use App\Enums\BankAccountType;
use App\Enums\PayoutAccountType;
use App\Enums\PixKeyType;
use App\Traits\BelongsToTeacher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Conta/chave de recebimento do treinador (destino dos repasses).
 *
 * Cada treinador pode manter várias (chave Pix ou conta bancária) e eleger
 * uma como padrão (`is_default`) — a sugerida ao solicitar um saque. O
 * `teacher_id` é sempre setado pelos Services (fora do fillable).
 */
class TeacherPayoutAccount extends Model
{
    use BelongsToTeacher, HasFactory;

    protected $fillable = [
        'type',
        'label',
        'pix_key',
        'pix_key_type',
        'bank_name',
        'bank_code',
        'agency',
        'account',
        'account_digit',
        'account_type',
        'holder_name',
        'holder_document',
        'is_default',
    ];

    protected $casts = [
        'type' => PayoutAccountType::class,
        'pix_key_type' => PixKeyType::class,
        'account_type' => BankAccountType::class,
        'is_default' => 'boolean',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function isPix(): bool
    {
        return $this->type === PayoutAccountType::PIX;
    }

    /**
     * Descrição legível do destino — usada na tela e congelada no Payout
     * (`destination`) no momento da solicitação do saque.
     */
    public function description(): string
    {
        if ($this->isPix()) {
            $type = $this->pix_key_type instanceof PixKeyType
                ? $this->pix_key_type->value
                : null;

            return 'Pix'.($type ? ' ('.(PixKeyType::labels()[$type] ?? $type).')' : '').' · '.$this->formattedPixKey();
        }

        $account = $this->account;

        // `empty('0')` é true no PHP: comparar explicitamente para não perder
        // o dígito "0" (conta 56789-0).
        if ($this->account_digit !== null && $this->account_digit !== '') {
            $account .= '-'.$this->account_digit;
        }

        $parts = array_filter([
            $this->bank_name,
            $this->agency ? 'Ag '.$this->agency : null,
            $account ? 'Conta '.$account : null,
        ]);

        return implode(' · ', $parts) ?: 'Conta bancária';
    }

    /**
     * Chave Pix legível: CPF/CNPJ pontuados e telefone no formato
     * "(DDD) número" (o valor guardado é só dígitos / E.164).
     */
    private function formattedPixKey(): ?string
    {
        $key = $this->pix_key;

        if ($key === null) {
            return null;
        }

        return $this->pix_key_type?->format($key) ?? $key;
    }

    /**
     * Como o destino é referenciado nas listas: o rótulo do treinador (quando
     * houver) com a descrição entre parênteses.
     */
    public function displayName(): string
    {
        return $this->label ? $this->label.' — '.$this->description() : $this->description();
    }
}
