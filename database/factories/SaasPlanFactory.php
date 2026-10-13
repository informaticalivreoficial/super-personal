<?php

namespace Database\Factories;

use App\Models\SaasPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SaasPlanFactory extends Factory
{
    protected $model = SaasPlan::class;

    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(8),
            'max_students' => fake()->randomElement([10, 30, null]),
            'price_monthly' => fake()->randomElement([0, 29.90, 49.90, 99.90]),
            'price_quarterly' => 0,
            'price_annual' => 0,
            'active' => true,
            'sort_order' => 0,
        ];
    }
}
