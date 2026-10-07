<?php

namespace Database\Factories;

use App\Models\CatPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CatPost>
 */
class CatPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_pai' => null,
            'title' => $this->faker->words(3, true),
            'content' => $this->faker->optional()->paragraph(),
            'slug' => Str::slug($this->faker->unique()->words(3, true)),
            'tags' => implode(',', $this->faker->words(5)),
            'views' => $this->faker->numberBetween(0, 1000),
            'type' => $this->faker->randomElement([
                'noticia',
                'artigo',
                'pagina',
            ]),
            'status' => $this->faker->randomElement([0, 1]),
        ];
    }
}
