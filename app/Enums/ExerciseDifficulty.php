<?php

namespace App\Enums;

enum ExerciseDifficulty: string
{
    case EASY = 'easy';
    case MEDIUM = 'medium';
    case HARD = 'hard';

    public static function labels(): array
    {
        return [
            self::EASY->value => 'Fácil',
            self::MEDIUM->value => 'Médio',
            self::HARD->value => 'Difícil',
        ];
    }
}
