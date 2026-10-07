<?php

namespace Database\Factories;

use App\Enums\TrainingSessionStatus;
use App\Models\Sport;
use App\Models\TrainingSession;
use App\Models\TrainingWeek;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class TrainingSessionFactory extends Factory
{
    protected $model = TrainingSession::class;

    public function definition(): array
    {
        return [
            'training_week_id' => TrainingWeek::factory(),
            'teacher_id' => fn (array $attributes) => $this->resolveFromWeek($attributes['training_week_id'], 'teacher_id'),
            'student_id' => fn (array $attributes) => $this->resolveFromWeek($attributes['training_week_id'], 'student_id'),
            'sport_id' => Sport::factory(),
            'exercise_id' => null,
            'title' => fake()->randomElement([
                'Intervalado 6x800m',
                'Rodagem contínua',
                'Longão de bike',
                'Natação técnica',
                'Treino de força',
                'Corrida leve',
            ]),
            'description' => fake()->sentence(10),
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'estimated_duration' => fake()->randomElement([1800, 2700, 3600, 5400]),
            'distance' => fake()->randomElement([null, 5000, 8000, 10000, 20000]),
            'intensity' => fake()->randomElement(['Z1', 'Z2', 'Z3', 'Z4', 'RPE 6']),
            'target_pace' => fake()->optional()->randomElement(['5:30/km', '6:00/km', '1:50/100m']),
            'target_heart_rate' => fake()->optional()->numberBetween(120, 175),
            'target_power' => fake()->optional()->numberBetween(150, 280),
            'instructions' => fake()->paragraph(),
            'coach_notes' => fake()->optional()->sentence(8),
            'status' => TrainingSessionStatus::PLANNED,
            'sort_order' => 0,
        ];
    }

    public function forWeek(TrainingWeek $week, array $overrides = []): static
    {
        return $this->state(fn () => array_merge([
            'training_week_id' => $week->id,
            'teacher_id' => $week->teacher_id,
        ], $overrides));
    }

    private function resolveFromWeek(int $weekId, string $column): ?int
    {
        $planId = DB::table('training_weeks')->where('id', $weekId)->value('training_plan_id');

        if ($column === 'teacher_id') {
            return (int) DB::table('training_plans')->where('id', $planId)->value('teacher_id');
        }

        return (int) DB::table('training_plans')->where('id', $planId)->value('student_id');
    }
}
