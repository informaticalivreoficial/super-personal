<?php

namespace App\Enums;

enum TrainingPlanStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public static function labels(): array
    {
        return [
            self::DRAFT->value => 'Rascunho',
            self::ACTIVE->value => 'Ativo',
            self::COMPLETED->value => 'Concluído',
            self::CANCELLED->value => 'Cancelado',
        ];
    }
}
