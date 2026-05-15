<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds cancelled tasks backdated 35–90 days ago to test the TaskCleanup cron job.
 *
 * Usage:
 *   php artisan db:seed --class=CancelledTasksSeeder
 */
class CancelledTasksSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $userIds = User::pluck('id')->toArray();
        $teamIds = Team::pluck('id')->toArray();

        if (! $admin || empty($teamIds)) {
            $this->command->warn('No admin user or teams found — skipping CancelledTasksSeeder.');
            return;
        }

        $titles = [
            'Migrate legacy database schema',
            'Update payment gateway integration',
            'Remove deprecated API endpoints',
            'Fix broken SSO config',
            'Refactor monolith auth module',
            'Archive old marketing assets',
            'Decommission staging server',
            'Clean up test accounts',
        ];

        foreach ($titles as $i => $title) {
            $daysAgo = 35 + ($i * 7);   // 35, 42, 49 … 84 days ago

            // Skip if a task with this title already exists (idempotent)
            if (Task::where('title', $title)->exists()) {
                continue;
            }

            $task = Task::create([
                'title'       => $title,
                'description' => 'Auto-generated test task for task-cleanup cron validation.',
                'status'      => Task::STATUS_CANCELLED,
                'priority'    => ['low', 'medium', 'high'][$i % 3],
                'team_id'     => $teamIds[array_rand($teamIds)],
                'created_by'  => $admin->id,
                'assigned_to' => $userIds[array_rand($userIds)],
            ]);

            // Backdate via raw query so model events don't overwrite the timestamp
            DB::table('tasks')->where('id', $task->id)->update([
                'updated_at' => now()->subDays($daysAgo)->toDateTimeString(),
                'created_at' => now()->subDays($daysAgo + 10)->toDateTimeString(),
            ]);
        }

        $count = Task::where('status', Task::STATUS_CANCELLED)
            ->whereDate('updated_at', '<', now()->subDays(30))
            ->count();

        $this->command->info("CancelledTasksSeeder: {$count} cancelled tasks ready for cleanup.");
    }
}
