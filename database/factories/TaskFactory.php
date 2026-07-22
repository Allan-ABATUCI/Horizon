<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userId = fn () => User::inRandomOrder()->value('id') ?? 1;

        $startDate = fake()->dateTimeBetween('-1 month', '+11 months');
        $endDate = (clone $startDate)->modify('+'.fake()->numberBetween(1, 21).' days');

        return [
            'name' => fake()->sentence(),
            'description' => fake()->realText(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => fake()->randomElement(['en attente', 'en cours', 'terminé']),
            'priority' => fake()->randomElement(['basse', 'moyenne', 'haute']),
            'image_path' => fake()->imageUrl(),
            'assigned_user_id' => $userId(),
            'created_by' => $userId(),
            'updated_by' => $userId(),
            'updated_at' => now(),
            'created_at' => now(),
        ];
    }
}
