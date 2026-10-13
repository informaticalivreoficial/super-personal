<?php

namespace Database\Factories;

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'teacher_id' => Teacher::factory(),
            'plan_id' => SaasPlan::factory(),
            'period' => BillingPeriod::MONTHLY,
            'gateway' => Subscription::GATEWAY_MANUAL,
            'status' => SubscriptionStatus::ACTIVE,
            'amount' => 99.90,
            'starts_at' => now()->format('Y-m-d'),
            'trial_ends_at' => null,
            'ends_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function forTeacher(Teacher $teacher): static
    {
        return $this->state(fn () => ['teacher_id' => $teacher->id]);
    }
}
