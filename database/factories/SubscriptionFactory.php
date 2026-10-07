<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
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
            'plan' => 'basic',
            'status' => SubscriptionStatus::TRIAL,
            'amount' => 99.90,
            'starts_at' => now()->format('Y-m-d'),
            'trial_ends_at' => now()->addDays(14)->format('Y-m-d'),
            'ends_at' => null,
            'cancelled_at' => null,
        ];
    }
}
