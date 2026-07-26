<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Attache automatiquement le créateur (et les assignés des tâches déjà
     * créées, utile pour hasTasks()) comme membres du projet.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Project $project) {
            $memberIds = $project->tasks()->pluck('assigned_user_id')->push($project->created_by)->unique();
            $project->members()->syncWithoutDetaching($memberIds);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userId = fn () => User::inRandomOrder()->value('id') ?? 1;

        return [
            'name' => fake()->sentence(),
            'description' => fake()->realText(),
            'end_date' => fake()->dateTimeBetween('now', '+1 year'),
            'status' => fake()->randomElement(['en attente', 'en cours', 'terminé']),
            'image_path' => fake()->imageUrl(),
            'created_by' => $userId(),
            'updated_by' => $userId(),
            'updated_at' => now(),
            'created_at' => now(),
        ];
    }
}
