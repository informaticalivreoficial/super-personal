<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case PIX = 'pix';
    case BANK_TRANSFER = 'bank_transfer';
    case CREDIT_CARD = 'credit_card';
    case CASH = 'cash';
    case OTHER = 'other';

    public static function labels(): array
    {
        return [
            self::PIX->value => 'Pix',
            self::BANK_TRANSFER->value => 'Transferência bancária',
            self::CREDIT_CARD->value => 'Cartão de crédito',
            self::CASH->value => 'Dinheiro',
            self::OTHER->value => 'Outro',
        ];
    }
}
