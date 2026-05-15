<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@test.com')->first();
        $manager = User::where('email', 'manager@test.com')->first();
        $members = User::where('role', User::ROLE_MEMBER)->get();

        // ── Engineering Team (4 members) ──────────────────────────────────────
        $engineering = Team::firstOrCreate(
            ['name' => 'Engineering'],
            ['created_by' => $admin->id]
        );

        // Admin as lead
        TeamMember::firstOrCreate(
            ['team_id' => $engineering->id, 'user_id' => $admin->id],
            ['role' => TeamMember::ROLE_LEAD]
        );

        // Manager as lead
        TeamMember::firstOrCreate(
            ['team_id' => $engineering->id, 'user_id' => $manager->id],
            ['role' => TeamMember::ROLE_LEAD]
        );

        // First 4 members
        foreach ($members->take(4) as $member) {
            TeamMember::firstOrCreate(
                ['team_id' => $engineering->id, 'user_id' => $member->id],
                ['role' => TeamMember::ROLE_MEMBER]
            );
        }

        // ── Marketing Team (3 members) ────────────────────────────────────────
        $marketing = Team::firstOrCreate(
            ['name' => 'Marketing'],
            ['created_by' => $manager->id]
        );

        TeamMember::firstOrCreate(
            ['team_id' => $marketing->id, 'user_id' => $manager->id],
            ['role' => TeamMember::ROLE_LEAD]
        );

        foreach ($members->slice(1, 3) as $member) {
            TeamMember::firstOrCreate(
                ['team_id' => $marketing->id, 'user_id' => $member->id],
                ['role' => TeamMember::ROLE_MEMBER]
            );
        }

        // ── Sales Team (2 members) ────────────────────────────────────────────
        $sales = Team::firstOrCreate(
            ['name' => 'Sales'],
            ['created_by' => $admin->id]
        );

        TeamMember::firstOrCreate(
            ['team_id' => $sales->id, 'user_id' => $admin->id],
            ['role' => TeamMember::ROLE_LEAD]
        );

        foreach ($members->slice(4, 2) as $member) {
            TeamMember::firstOrCreate(
                ['team_id' => $sales->id, 'user_id' => $member->id],
                ['role' => TeamMember::ROLE_MEMBER]
            );
        }

        $this->command->info('Teams seeded: Engineering, Marketing, Sales.');
    }
}
