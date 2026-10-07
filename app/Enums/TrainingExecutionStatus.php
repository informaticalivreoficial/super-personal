<?php

namespace App\Enums;

enum TrainingExecutionStatus: string
{
    case STARTED = 'started';
    case COMPLETED = 'completed';
    case ABANDONED = 'abandoned';

    public static function labels(): array
    {
        return [
            self::STARTED->value => 'Iniciado',
            self::COMPLETED->value => 'Concluído',
            self::ABANDONED->value => 'Abandonado',
        ];
    }
}
