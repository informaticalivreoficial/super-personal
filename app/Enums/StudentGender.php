<?php

namespace App\Enums;

enum StudentGender: string
{
    case MALE = 'male';
    case FEMALE = 'female';
    case OTHER = 'other';

    public static function labels(): array
    {
        return [
            self::MALE->value => 'Masculino',
            self::FEMALE->value => 'Feminino',
            self::OTHER->value => 'Outro',
        ];
    }
}
