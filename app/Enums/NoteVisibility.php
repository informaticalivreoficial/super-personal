<?php

namespace App\Enums;

enum NoteVisibility: string
{
    case PRIVATE = 'private';
    case SHARED = 'shared';

    public static function labels(): array
    {
        return [
            self::PRIVATE->value => 'Privado (só o treinador)',
            self::SHARED->value => 'Compartilhado com o aluno',
        ];
    }
}
