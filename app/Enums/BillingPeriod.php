<?php

namespace App\Enums;

enum BillingPeriod: string
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case ANNUAL = 'annual';

    public static function labels(): array
    {
        return [
            self::MONTHLY->value => 'Mensal',
            self::QUARTERLY->value => 'Trimestral',
            self::ANNUAL->value => 'Anual',
        ];
    }

    /** Duração da vigência em meses (para calcular ends_at). */
    public function months(): int
    {
        return match ($this) {
            self::MONTHLY => 1,
            self::QUARTERLY => 3,
            self::ANNUAL => 12,
        };
    }
}
