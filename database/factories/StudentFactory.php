<?php

namespace Database\Factories;

use App\Enums\StudentGender;
use App\Enums\StudentLevel;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'teacher_id' => Teacher::factory(),
            'user_id' => User::factory([
                'role' => UserRole::STUDENT,
                'status' => 1,
                'email_verified_at' => now(),
            ]),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'birth_date' => fake()->dateTimeBetween('-55 years', '-16 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(StudentGender::cases()),
            'document' => fake()->cpf(),
            'height' => fake()->numberBetween(155, 195),
            'initial_weight' => fake()->randomFloat(2, 60, 110),
            'current_weight' => fake()->randomFloat(2, 60, 110),
            'target_weight' => fake()->randomFloat(2, 58, 100),
            'goal' => fake()->randomElement(['Emagrecimento', 'Condicionamento físico', 'Preparação para prova', 'Saúde geral']),
            'fitness_level' => fake()->randomElement(StudentLevel::cases()),
            'training_experience' => fake()->randomElement(['Nenhuma', 'Até 1 ano', '1 a 3 anos', 'Mais de 3 anos']),
            'available_days' => fake()->randomElements([1, 2, 3, 4, 5, 6, 7], 3),
            'observations' => fake()->optional()->sentence(8),
            'active' => true,
            'started_at' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ];
    }

    public function withoutAccount(): static
    {
        return $this->state(fn () => ['user_id' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }

    public function forTeacher(Teacher $teacher): static
    {
        return $this->state(fn () => ['teacher_id' => $teacher->id]);
    }
}
