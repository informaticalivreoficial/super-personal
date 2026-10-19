<?php

namespace App\Enums;

/**
 * Tipo da conta bancária usada no recebimento do repasse.
 */
enum BankAccountType: string
{
    case CHECKING = 'checking';
    case SAVINGS = 'savings';

    public static function labels(): array
    {
        return [
            self::CHECKING->value => 'Conta corrente',
            self::SAVINGS->value => 'Conta poupança',
        ];
    }
}
