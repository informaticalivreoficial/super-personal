<?php

namespace App\Enums;

/**
 * Tipo de conta/chave usada pelo treinador para receber os repasses.
 */
enum PayoutAccountType: string
{
    case PIX = 'pix';
    case BANK_ACCOUNT = 'bank_account';

    public static function labels(): array
    {
        return [
            self::PIX->value => 'Chave Pix',
            self::BANK_ACCOUNT->value => 'Conta bancária',
        ];
    }
}
