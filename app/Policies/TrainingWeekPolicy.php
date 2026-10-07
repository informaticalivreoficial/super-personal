<?php

namespace App\Policies;

use App\Models\TrainingPlan;
use App\Models\TrainingWeek;
use App\Models\User;

class TrainingWeekPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isPlatformAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isTeacher() || $user->isStudent();
    }

    public function view(User $user, TrainingWeek $week): bool
    {
        if ($user->isTeacher()) {
            return $week->teacher_id === $user->resolveTenantId();
        }

        return $user->isStudent() && $week->plan?->student_id === $user->student?->id;
    }

    /**
     * Criar semana = mutar o plano (recebe o plano como contexto).
     */
    public function create(User $user, TrainingPlan $plan): bool
    {
        return $user->isTeacher() && $plan->teacher_id === $user->resolveTenantId();
    }

    public function update(User $user, TrainingWeek $week): bool
    {
        return $user->isTeacher() && $week->teacher_id === $user->resolveTenantId();
    }

    public function delete(User $user, TrainingWeek $week): bool
    {
        return $this->update($user, $week);
    }
}
