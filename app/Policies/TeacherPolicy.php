<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;

/**
 * Gestão de treinadores (tenants) é exclusiva do admin da plataforma.
 * O treinador não gerencia outros treinadores; a landing page pública
 * (dados de perfil do próprio tenant) tem regra própria em updateLanding().
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

    /**
     * Landing page pública: o treinador edita a própria (tenant dele);
     * o admin edita a de qualquer tenant. Professor A nunca edita a do B.
     */
    public function updateLanding(User $user, Teacher $teacher): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }

        return $user->isTeacher() && $user->teacher?->id === $teacher->id;
    }
}
