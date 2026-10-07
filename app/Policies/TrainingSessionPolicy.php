<?php

namespace App\Policies;

use App\Models\TrainingSession;
use App\Models\User;

class TrainingSessionPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isPlatformAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isTeacher() || $user->isStudent();
    }

    public function view(User $user, TrainingSession $session): bool
    {
        if ($user->isTeacher()) {
            return $session->teacher_id === $user->resolveTenantId();
        }

        return $user->isStudent() && $session->student_id === $user->student?->id;
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function update(User $user, TrainingSession $session): bool
    {
        return $user->isTeacher() && $session->teacher_id === $user->resolveTenantId();
    }

    public function delete(User $user, TrainingSession $session): bool
    {
        return $this->update($user, $session);
    }

    /**
     * Ações do aluno sobre o próprio treino: iniciar, concluir, pular.
     */
    public function start(User $user, TrainingSession $session): bool
    {
        return $user->isStudent() && $session->student_id === $user->student?->id;
    }

    public function complete(User $user, TrainingSession $session): bool
    {
        return $this->start($user, $session);
    }

    public function skip(User $user, TrainingSession $session): bool
    {
        return $this->start($user, $session);
    }
}
