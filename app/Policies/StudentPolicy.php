<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isPlatformAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isTeacher();
    }

    public function view(User $user, Student $student): bool
    {
        if ($user->isTeacher()) {
            return $student->teacher_id === $user->resolveTenantId();
        }

        return $user->isStudent() && $student->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function update(User $user, Student $student): bool
    {
        return $this->view($user, $student);
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->isTeacher() && $student->teacher_id === $user->resolveTenantId();
    }
}
