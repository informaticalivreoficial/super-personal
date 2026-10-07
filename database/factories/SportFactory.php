<?php

namespace Database\Factories;

use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

class SportFactory extends Factory
{
    protected $model = Sport::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Corrida', 'Ciclismo', 'Natação', 'Triathlon', 'Musculação', 'Funcional', 'Mobilidade', 'Emagrecimento']),
            'description' => fake()->sentence(8),
            'icon' => null,
            'active' => true,
        ];
    }
}
