<?php

namespace App\Policies;

use App\Models\SaasPlan;
use App\Models\User;

/**
 * Planos do SaaS: catálogo da plataforma — gestão exclusiva do admin.
 */
class SaasPlanPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isPlatformAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, SaasPlan $plan): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, SaasPlan $plan): bool
    {
        return false;
    }

    public function delete(User $user, SaasPlan $plan): bool
    {
        return false;
    }
}
