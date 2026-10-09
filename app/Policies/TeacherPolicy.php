<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;

/**
 * Gestão de treinadores (tenants) é exclusiva do admin da plataforma.
 * O treinador não gerencia outros treinadores; o próprio perfil
 * (edição de dados públicos) entra num incremento próprio depois.
 */
class TeacherPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function view(User $user, Teacher $teacher): bool
    {
        return $user->isPlatformAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $user->isPlatformAdmin();
    }
}
