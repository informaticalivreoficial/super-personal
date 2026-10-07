<?php

namespace Database\Factories;

use App\Models\TrainingPlan;
use App\Models\TrainingWeek;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class TrainingWeekFactory extends Factory
{
    protected $model = TrainingWeek::class;

    public function definition(): array
    {
        return [
            'training_plan_id' => TrainingPlan::factory(),
            'teacher_id' => fn (array $attributes) => DB::table('training_plans')
                ->where('id', $attributes['training_plan_id'])
                ->value('teacher_id'),
            'week_number' => 1,
            'name' => fake()->optional()->words(3, true),
            'start_date' => now()->startOfWeek()->format('Y-m-d'),
            'end_date' => now()->endOfWeek()->format('Y-m-d'),
            'objective' => fake()->sentence(6),
            'notes' => fake()->optional()->sentence(6),
            'status' => 'pending',
        ];
    }

    public function forPlan(TrainingPlan $plan, int $weekNumber = 1): static
    {
        return $this->state(fn () => [
            'training_plan_id' => $plan->id,
            'teacher_id' => $plan->teacher_id,
            'week_number' => $weekNumber,
            'start_date' => $plan->start_date->copy()->addWeeks($weekNumber - 1)->format('Y-m-d'),
            'end_date' => $plan->start_date->copy()->addWeeks($weekNumber - 1)->addDays(6)->format('Y-m-d'),
        ]);
    }
}
