<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\StudentProgress;
use App\Models\User;

class StudentProgressPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isPlatformAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isTeacher() || $user->isStudent();
    }

    public function view(User $user, StudentProgress $progress): bool
    {
        if ($user->isTeacher()) {
            return $progress->teacher_id === $user->resolveTenantId();
        }

        return $user->isStudent() && $progress->student_id === $user->student?->id;
    }

    /**
     * Só o treinador dono do aluno registra avaliação.
     */
    public function create(User $user, Student $student): bool
    {
        return $user->isTeacher() && $student->teacher_id === $user->resolveTenantId();
    }

    public function delete(User $user, StudentProgress $progress): bool
    {
        return $user->isTeacher() && $progress->teacher_id === $user->resolveTenantId();
    }
}
