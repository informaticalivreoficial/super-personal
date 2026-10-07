<?php

namespace App\Policies;

use App\Models\TrainingPlan;
use App\Models\User;

class TrainingPlanPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isPlatformAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isTeacher() || $user->isStudent();
    }

    public function view(User $user, TrainingPlan $plan): bool
    {
        if ($user->isTeacher()) {
            return $plan->teacher_id === $user->resolveTenantId();
        }

        return $user->isStudent() && $plan->student_id === $user->student?->id;
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function update(User $user, TrainingPlan $plan): bool
    {
        return $user->isTeacher() && $plan->teacher_id === $user->resolveTenantId();
    }

    public function delete(User $user, TrainingPlan $plan): bool
    {
        return $this->update($user, $plan);
    }
}
