<?php

namespace App\Policies;

use App\Models\TrainingExecution;
use App\Models\TrainingSession;
use App\Models\User;

class TrainingExecutionPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isPlatformAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isTeacher() || $user->isStudent();
    }

    public function view(User $user, TrainingExecution $execution): bool
    {
        if ($user->isTeacher()) {
            return $execution->teacher_id === $user->resolveTenantId();
        }

        return $user->isStudent() && $execution->student_id === $user->student?->id;
    }

    /**
     * O aluno só pode registrar execução no treino dele.
     */
    public function create(User $user, TrainingSession $session): bool
    {
        return $user->isStudent() && $session->student_id === $user->student?->id;
    }

    public function update(User $user, TrainingExecution $execution): bool
    {
        return $user->isStudent() && $execution->student_id === $user->student?->id;
    }
}
