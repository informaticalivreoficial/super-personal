<?php

namespace Database\Factories;

use App\Enums\TrainingExecutionStatus;
use App\Models\TrainingExecution;
use App\Models\TrainingSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class TrainingExecutionFactory extends Factory
{
    protected $model = TrainingExecution::class;

    public function definition(): array
    {
        return [
            'training_session_id' => TrainingSession::factory(),
            'teacher_id' => fn (array $attributes) => (int) DB::table('training_sessions')
                ->where('id', $attributes['training_session_id'])
                ->value('teacher_id'),
            'student_id' => fn (array $attributes) => (int) DB::table('training_sessions')
                ->where('id', $attributes['training_session_id'])
                ->value('student_id'),
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'duration' => fake()->numberBetween(1200, 5400),
            'distance' => fake()->randomElement([3000, 5000, 8000, 10000, 40000]),
            'average_heart_rate' => fake()->numberBetween(120, 175),
            'max_heart_rate' => fake()->numberBetween(150, 190),
            'average_pace' => fake()->optional()->randomElement(['5:10/km', '6:20/km', '1:45/100m']),
            'average_power' => fake()->optional()->numberBetween(150, 280),
            'perceived_effort' => fake()->numberBetween(3, 10),
            'feeling' => fake()->randomElement(['Ótimo', 'Bom', 'Regular', 'Ruim']),
            'notes' => fake()->optional()->sentence(8),
            'status' => TrainingExecutionStatus::COMPLETED,
        ];
    }

    public function forSession(TrainingSession $session): static
    {
        return $this->state(fn () => [
            'training_session_id' => $session->id,
            'teacher_id' => $session->teacher_id,
            'student_id' => $session->student_id,
        ]);
    }
}
