<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\StudentNote;
use App\Models\User;

/**
 * Observações do professor sobre o aluno (student_notes).
 * Admin tem bypass; professor só acessa as próprias (tenant).
 */
class StudentNotePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isPlatformAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isTeacher();
    }

    public function view(User $user, StudentNote $note): bool
    {
        return $user->isTeacher() && $note->teacher_id === $user->resolveTenantId();
    }

    /**
     * Só o professor dono do aluno cria observação sobre ele.
     */
    public function create(User $user, Student $student): bool
    {
        return $user->isTeacher() && $student->teacher_id === $user->resolveTenantId();
    }

    public function delete(User $user, StudentNote $note): bool
    {
        return $user->isTeacher() && $note->teacher_id === $user->resolveTenantId();
    }
}
