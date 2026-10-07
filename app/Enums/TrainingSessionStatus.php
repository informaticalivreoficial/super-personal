<?php

namespace App\Enums;

enum TrainingSessionStatus: string
{
    case PLANNED = 'planned';
    case COMPLETED = 'completed';
    case SKIPPED = 'skipped';
    case CANCELLED = 'cancelled';

    public static function labels(): array
    {
        return [
            self::PLANNED->value => 'Planejado',
            self::COMPLETED->value => 'Concluído',
            self::SKIPPED->value => 'Pulado',
            self::CANCELLED->value => 'Cancelado',
        ];
    }
}
