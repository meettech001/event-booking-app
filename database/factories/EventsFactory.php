<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Events>
 */
class EventsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'    => 1, // You can set this to a valid user ID or use a factory for user.
            'title'      => $this->faker->sentence,
            'description'=> $this->faker->paragraph,
            'start_time' => $this->faker->dateTime,
            'end_time'   => $this->faker->dateTime,
            'capacity'   => $this->faker->numberBetween(10, 500),
            'country'    => $this->faker->randomElement(['in', 'uk', 'usa']),
        ];
    }
}
