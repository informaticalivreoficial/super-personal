<?php

namespace App\Enums;

enum TrainingWeekStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';

    public static function labels(): array
    {
        return [
            self::PENDING->value => 'Pendente',
            self::ACTIVE->value => 'Em andamento',
            self::COMPLETED->value => 'Concluída',
        ];
    }
}
