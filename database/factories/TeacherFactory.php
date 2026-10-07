<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory([
                'role' => UserRole::TEACHER,
                'status' => 1,
                'email_verified_at' => now(),
            ]),
            'name' => fake()->name(),
            'bio' => fake()->sentence(12),
            'phone' => fake()->phoneNumber(),
            'document' => fake()->cpf(),
            'birth_date' => fake()->dateTimeBetween('-45 years', '-25 years')->format('Y-m-d'),
            'cref' => strtoupper(fake()->randomLetter()).'-'.fake()->numberBetween(100000, 999999),
            'specialty' => fake()->randomElement(['Triathlon', 'Corrida', 'Ciclismo', 'Natação', 'Emagrecimento']),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
