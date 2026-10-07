<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';

    public static function labels(): array
    {
        return [
            self::PENDING->value => 'Pendente',
            self::PAID->value => 'Pago',
            self::OVERDUE->value => 'Atrasado',
            self::CANCELLED->value => 'Cancelado',
        ];
    }
}
