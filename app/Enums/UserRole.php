<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case TEACHER = 'teacher';
    case STUDENT = 'student';

    public static function labels(): array
    {
        return [
            self::ADMIN->value => 'Administrador',
            self::TEACHER->value => 'Treinador',
            self::STUDENT->value => 'Aluno',
        ];
    }
}
