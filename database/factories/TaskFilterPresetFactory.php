<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFilterPresetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name'    => $this->faker->words(3, true),
            'filters' => [
                'status'   => $this->faker->randomElement(['pending', 'in_progress', 'completed']),
                'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            ],
        ];
    }
}
