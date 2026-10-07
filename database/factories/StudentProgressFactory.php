<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudentProgress;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class StudentProgressFactory extends Factory
{
    protected $model = StudentProgress::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'teacher_id' => fn (array $attributes) => DB::table('students')
                ->where('id', $attributes['student_id'])
                ->value('teacher_id'),
            'recorded_at' => now()->format('Y-m-d'),
            'weight' => fake()->randomFloat(2, 60, 110),
            'body_fat' => fake()->optional()->randomFloat(1, 8, 35),
            'measurements' => fake()->optional()->boolean() ? [
                'cintura' => fake()->numberBetween(70, 105),
                'quadril' => fake()->numberBetween(90, 115),
                'braco' => fake()->numberBetween(28, 42),
            ] : null,
            'resting_heart_rate' => fake()->optional()->numberBetween(45, 75),
            'max_heart_rate' => fake()->optional()->numberBetween(170, 195),
            'ftp' => fake()->optional()->numberBetween(150, 320),
            'running_pace' => fake()->optional()->randomElement(['5:00/km', '5:30/km', '6:00/km']),
            'swimming_pace' => fake()->optional()->randomElement(['1:40/100m', '1:55/100m']),
            'notes' => fake()->optional()->sentence(8),
        ];
    }

    public function forStudent(Student $student): static
    {
        return $this->state(fn () => [
            'student_id' => $student->id,
            'teacher_id' => $student->teacher_id,
        ]);
    }
}
