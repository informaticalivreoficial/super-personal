<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function create(User $user)
    {
        return $user->isSuperAdmin() || $user->isAdmin() || $user->isManager();
    }


    public function view(User $user, User $model): bool
    {
        // SuperAdmin e Admin veem todos
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }        
        // Manager vê apenas colaboradores da mesma empresa
        if ($user->isManager()) {
            return
                $model->isEmployee();
        }
        // Employee vê apenas ele mesmo
        if ($user->isEmployee()) {
            return $user->id === $model->id;
        }
        return false;
    }

    public function update(User $user, User $model): bool
    {
        // 🚀 Super Admin pode tudo
        if ($user->isSuperAdmin()) {
            return true;
        }

        // 🛡 Admin pode todos, menos Super Admin
        if ($user->isAdmin()) {
            return ! $model->isSuperAdmin();
        }

        // 🧑‍💼 Manager
        if ($user->isManager()) {
            return
                (
                    $model->isEmployee()
                )
                || $user->id === $model->id;
        }

        // 👷 Employee → somente o próprio perfil
        if ($user->isEmployee()) {
            return $user->id === $model->id;
        }

        return false;
    }

    public function delete(User $user, User $model): bool
    {
        // Ninguém pode deletar a si mesmo
        if ($user->id === $model->id) {
            return false;
        }

        // SuperAdmin pode deletar qualquer um
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admin deleta qualquer um EXCETO SuperAdmin
        if ($user->isAdmin()) {
            return !$model->isSuperAdmin();
        }

        // Manager deleta apenas employees
        if ($user->isManager()) {
            return $model->isEmployee();
        }

        return false;
    }
}
