<?php

namespace Database\Factories;

use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Restaurant>
 */
class RestaurantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Restaurant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'restaurant_id' => fake()->unique()->regexify('[a-z]{5}'),
            'cuisine' => fake()->word(),
            'price' => fake()->numberBetween(1, 5),
            'rating' => fake()->numberBetween(1, 5),
            'location' => fake()->streetAddress(),
            'description' => fake()->optional(0.5, null)->sentence(),
        ];
    }
}
