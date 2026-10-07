<?php

namespace Database\Factories;

use App\Models\CatPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'autor' => User::factory(),
            'category' => CatPost::factory(),

            'type' => $this->faker->randomElement([
                'noticia',
                'artigo',
                'pagina',
            ]),
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'slug' => Str::slug($this->faker->unique()->sentence()),
            'tags' => implode(',', $this->faker->words(5)),
            'views' => $this->faker->numberBetween(0, 5000),

            'cat_pai' => null,
            'comments' => $this->faker->numberBetween(0, 50),
            'status' => $this->faker->randomElement([0, 1]),
            'highlight' => $this->faker->boolean(),
            'menu' => $this->faker->boolean(),

            'thumb_caption' => $this->faker->sentence(),
            'publish_at' => $this->faker->optional()->date(),
        ];
    }
}
