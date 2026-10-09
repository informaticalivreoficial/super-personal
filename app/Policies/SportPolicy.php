<?php

namespace App\Policies;

use App\Models\Sport;
use App\Models\User;

/**
 * Modalidades (sports) são o catálogo GLOBAL da plataforma (sem tenant_id):
 * treinadores apenas consultam; criação/edição/exclusão é exclusiva do
 * administrador da plataforma (antes do MVP multi-tenant, um treinador não
 * pode alterar o catálogo visto por todos).
 */
class SportPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isPlatformAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function view(User $user, Sport $sport): bool
    {
        return $user->isPlatformAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function update(User $user, Sport $sport): bool
    {
        return $user->isPlatformAdmin();
    }

    public function delete(User $user, Sport $sport): bool
    {
        return $user->isPlatformAdmin();
    }
}
