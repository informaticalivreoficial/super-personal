<?php

namespace App\Enums;

enum StudentLevel: string
{
    case BEGINNER = 'beginner';
    case INTERMEDIATE = 'intermediate';
    case ADVANCED = 'advanced';

    public static function labels(): array
    {
        return [
            self::BEGINNER->value => 'Iniciante',
            self::INTERMEDIATE->value => 'Intermediário',
            self::ADVANCED->value => 'Avançado',
        ];
    }
}
