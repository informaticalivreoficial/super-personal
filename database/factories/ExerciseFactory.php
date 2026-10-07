<?php

namespace Database\Factories;

use App\Enums\ExerciseDifficulty;
use App\Models\Exercise;
use App\Models\Sport;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExerciseFactory extends Factory
{
    protected $model = Exercise::class;

    public function definition(): array
    {
        return [
            'teacher_id' => null,
            'sport_id' => Sport::factory(),
            'name' => fake()->unique()->randomElement([
                'Corrida intervalada 6x800m',
                'Rodagem contínua',
                'Longão',
                'Pedalada Z2',
                'Natação técnica de braçada',
                'Agachamento livre',
                'Prancha isométrica',
                'Mobilidade de quadril',
            ]),
            'description' => fake()->sentence(10),
            'instructions' => fake()->paragraph(),
            'video_url' => null,
            'image' => null,
            'difficulty' => fake()->randomElement(ExerciseDifficulty::cases()),
            'active' => true,
        ];
    }

    public function forTeacher(Teacher $teacher): static
    {
        return $this->state(fn () => ['teacher_id' => $teacher->id]);
    }
}
