<?php

namespace Database\Factories;

use App\Enums\TrainingPlanStatus;
use App\Models\Student;
use App\Models\TrainingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class TrainingPlanFactory extends Factory
{
    protected $model = TrainingPlan::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'teacher_id' => fn (array $attributes) => DB::table('students')
                ->where('id', $attributes['student_id'])
                ->value('teacher_id'),
            'name' => fake()->randomElement(['Preparação Triathlon Sprint', 'Emagrecimento 12 semanas', 'Preparação para meia maratona', 'Base para ciclismo']),
            'description' => fake()->paragraph(),
            'goal' => fake()->sentence(6),
            'start_date' => now()->subWeeks(2)->format('Y-m-d'),
            'end_date' => now()->addWeeks(10)->format('Y-m-d'),
            'status' => TrainingPlanStatus::ACTIVE,
            'notes' => fake()->optional()->sentence(8),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => TrainingPlanStatus::DRAFT]);
    }

    public function forStudent(Student $student): static
    {
        return $this->state(fn () => [
            'student_id' => $student->id,
            'teacher_id' => $student->teacher_id,
        ]);
    }
}
