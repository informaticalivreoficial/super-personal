<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isPlatformAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isTeacher() || $user->isStudent();
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->isTeacher()) {
            return $payment->teacher_id === $user->resolveTenantId();
        }

        return $user->isStudent() && $payment->student_id === $user->student?->id;
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->isTeacher() && $payment->teacher_id === $user->resolveTenantId();
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $this->update($user, $payment);
    }
}
