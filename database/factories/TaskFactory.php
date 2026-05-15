<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => Task::STATUS_PENDING,
            'priority' => fake()->randomElement(Task::PRIORITIES),
            'assigned_to' => User::factory(),
            'created_by' => User::factory(),
            'team_id' => Team::factory(),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+30 days'),
        ];
    }

    public function inProgress(): static
    {
        return $this->state(['status' => Task::STATUS_IN_PROGRESS]);
    }

    public function completed(): static
    {
        return $this->state(['status' => Task::STATUS_COMPLETED]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => Task::STATUS_CANCELLED]);
    }

    public function highPriority(): static
    {
        return $this->state(['priority' => Task::PRIORITY_HIGH]);
    }
}
