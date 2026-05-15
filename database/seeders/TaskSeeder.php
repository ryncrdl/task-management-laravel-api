<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@test.com')->first();
        $engineering = Team::where('name', 'Engineering')->first();
        $members = User::where('role', User::ROLE_MEMBER)->get();

        $member1 = $members->first();
        $member2 = $members->get(1);
        $member3 = $members->get(2);

        $tasks = [
            [
                'title' => 'Setup database',
                'description' => 'Configure and initialize the PostgreSQL database for production.',
                'status' => Task::STATUS_IN_PROGRESS,
                'priority' => Task::PRIORITY_HIGH,
                'assigned_to' => $member1?->id,
                'created_by' => $admin->id,
                'team_id' => $engineering->id,
                'due_date' => now()->addDays(3),
            ],
            [
                'title' => 'Write API docs',
                'description' => 'Create Swagger/OpenAPI documentation for all endpoints.',
                'status' => Task::STATUS_PENDING,
                'priority' => Task::PRIORITY_MEDIUM,
                'assigned_to' => $member2?->id,
                'created_by' => $admin->id,
                'team_id' => $engineering->id,
                'due_date' => now()->addDays(7),
            ],
            [
                'title' => 'Fix login bug',
                'description' => 'Resolve JWT token expiry issue causing repeated logouts.',
                'status' => Task::STATUS_COMPLETED,
                'priority' => Task::PRIORITY_HIGH,
                'assigned_to' => $member1?->id,
                'created_by' => $admin->id,
                'team_id' => $engineering->id,
                'due_date' => now()->subDays(1),
            ],
            [
                'title' => 'Design dashboard',
                'description' => 'Create wireframes and Figma mockups for the analytics dashboard.',
                'status' => Task::STATUS_IN_PROGRESS,
                'priority' => Task::PRIORITY_MEDIUM,
                'assigned_to' => $member3?->id,
                'created_by' => $admin->id,
                'team_id' => $engineering->id,
                'due_date' => now()->addDays(5),
            ],
            [
                'title' => 'Set up CI/CD pipeline',
                'description' => 'Configure GitHub Actions for automated testing and deployment.',
                'status' => Task::STATUS_PENDING,
                'priority' => Task::PRIORITY_HIGH,
                'assigned_to' => $member2?->id,
                'created_by' => $admin->id,
                'team_id' => $engineering->id,
                'due_date' => now()->addDays(14),
            ],
        ];

        foreach ($tasks as $taskData) {
            Task::firstOrCreate(
                ['title' => $taskData['title'], 'team_id' => $taskData['team_id']],
                $taskData
            );
        }

        $this->command->info('Sample tasks seeded: 5 tasks in Engineering team.');
    }
}
