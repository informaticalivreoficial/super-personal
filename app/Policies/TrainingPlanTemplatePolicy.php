<?php

namespace App\Policies;

use App\Models\TrainingPlanTemplate;
use App\Models\User;

class TrainingPlanTemplatePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isPlatformAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isTeacher();
    }

    public function view(User $user, TrainingPlanTemplate $template): bool
    {
        return $user->isTeacher() && $template->teacher_id === $user->resolveTenantId();
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function delete(User $user, TrainingPlanTemplate $template): bool
    {
        return $this->view($user, $template);
    }
}
