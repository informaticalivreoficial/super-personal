<?php

namespace Database\Factories;

use App\Enums\SessionItemType;
use App\Models\TrainingSession;
use App\Models\TrainingSessionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingSessionItemFactory extends Factory
{
    protected $model = TrainingSessionItem::class;

    public function definition(): array
    {
        return [
            'training_session_id' => TrainingSession::factory(),
            'exercise_id' => null,
            'type' => fake()->randomElement(SessionItemType::cases()),
            'title' => fake()->randomElement(['Aquecimento', 'Bloco principal', 'Recuperação', 'Volta à calma']),
            'description' => fake()->optional()->sentence(8),
            'duration' => fake()->optional()->numberBetween(300, 1800),
            'distance' => fake()->optional()->randomElement([200, 400, 800, 1000, 5000]),
            'repetitions' => fake()->optional()->numberBetween(3, 12),
            'sets' => fake()->optional()->numberBetween(2, 5),
            'rest' => fake()->optional()->numberBetween(30, 180),
            'target' => fake()->optional()->randomElement(['5:30/km', '85% FCmáx', '200W']),
            'intensity' => fake()->optional()->randomElement(['Z2', 'Z4', 'RPE 8']),
            'sort_order' => 0,
        ];
    }

    public function forSession(TrainingSession $session, int $sortOrder = 0): static
    {
        return $this->state(fn () => [
            'training_session_id' => $session->id,
            'sort_order' => $sortOrder,
        ]);
    }
}
