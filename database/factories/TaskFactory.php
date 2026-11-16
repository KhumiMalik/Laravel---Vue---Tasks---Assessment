<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => rtrim(fake()->sentence(fake()->numberBetween(3, 7)), '.'),
            'description' => fake()->optional(0.7)->paragraph(),
            'is_completed' => false,
        ];
    }

    public function completed(): static
    {
        return $this->state(['is_completed' => true]);
    }

    public function pending(): static
    {
        return $this->state(['is_completed' => false]);
    }
}
