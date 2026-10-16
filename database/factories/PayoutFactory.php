<?php

namespace Database\Factories;

use App\Enums\PayoutStatus;
use App\Models\Payout;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayoutFactory extends Factory
{
    protected $model = Payout::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 100, 2000);
        $fee = round($amount * 0.05, 2);

        return [
            'teacher_id' => Teacher::factory(),
            'amount' => $amount,
            'fee_total' => $fee,
            'net_amount' => round($amount - $fee, 2),
            'status' => PayoutStatus::REQUESTED,
            'paid_at' => null,
            'notes' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => PayoutStatus::PAID,
            'paid_at' => now()->subDays(2),
        ]);
    }

    public function forTeacher(Teacher $teacher): static
    {
        return $this->state(fn () => ['teacher_id' => $teacher->id]);
    }
}
