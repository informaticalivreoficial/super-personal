<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case TRIAL = 'trial';
    case ACTIVE = 'active';
    case PAST_DUE = 'past_due';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public static function labels(): array
    {
        return [
            self::TRIAL->value => 'Teste',
            self::ACTIVE->value => 'Ativa',
            self::PAST_DUE->value => 'Em atraso',
            self::CANCELLED->value => 'Cancelada',
            self::EXPIRED->value => 'Expirada',
        ];
    }
}
