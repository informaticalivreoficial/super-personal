<?php

namespace App\Policies;

use App\Models\User;

/**
 * Usuários (páginas legadas de gestão de usuários do starter).
 * Reconciliado com `users.role` (enum UserRole) — o spatie/laravel-permission
 * foi removido; não existem mais "manager"/"employee"/"super-admin".
 */
class UserPolicy
{
    public function before(User $user, string $ability, mixed ...$arguments): ?bool
    {
        if (! $user->isPlatformAdmin()) {
            return null;
        }

        // Ninguém exclui a si mesmo (inclusive o admin).
        if ($ability === 'delete' && isset($arguments[0]) && $arguments[0] instanceof User) {
            return $user->id !== $arguments[0]->id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isPlatformAdmin() || $user->id === $model->id;
    }

    public function update(User $user, User $model): bool
    {
        return $user->isPlatformAdmin() || $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isPlatformAdmin() && $user->id !== $model->id;
    }
}
