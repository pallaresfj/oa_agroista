<?php

namespace Database\Factories;

use App\Models\Campus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campus>
 */
class CampusFactory extends Factory
{
    protected $model = Campus::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Campus',
            'address' => fake()->address(),
            'latitude' => fake()->latitude(4.0, 11.0), // Colombia latitudes
            'longitude' => fake()->longitude(-79.0, -67.0), // Colombia longitudes
            'radius_meters' => fake()->numberBetween(50, 200),
            'qr_token' => Str::uuid()->toString(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the campus is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
