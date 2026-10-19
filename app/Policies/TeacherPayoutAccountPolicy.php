<?php

namespace App\Policies;

use App\Models\TeacherPayoutAccount;
use App\Models\User;

/**
 * Contas de recebimento são do treinador (dono do tenant). O admin da
 * plataforma tem bypass (`before`) mas não tem tenant — a área Financeiro
 * só é acessível ao próprio treinador.
 */
class TeacherPayoutAccountPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isPlatformAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isTeacher();
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function update(User $user, TeacherPayoutAccount $account): bool
    {
        return $user->isTeacher() && $account->teacher_id === $user->resolveTenantId();
    }

    public function delete(User $user, TeacherPayoutAccount $account): bool
    {
        return $this->update($user, $account);
    }
}
