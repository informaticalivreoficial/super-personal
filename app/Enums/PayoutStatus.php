<?php

namespace App\Enums;

/**
 * Situação do repasse da plataforma ao treinador.
 */
enum PayoutStatus: string
{
    case REQUESTED = 'requested';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    public static function labels(): array
    {
        return [
            self::REQUESTED->value => 'Solicitado',
            self::PAID->value => 'Pago',
            self::CANCELLED->value => 'Cancelado',
        ];
    }
}
