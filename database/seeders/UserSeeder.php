<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );

        // Manager
        User::firstOrCreate(
            ['email' => 'manager@test.com'],
            [
                'name' => 'Manager User',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_MANAGER,
                'is_active' => true,
            ]
        );

        // Team Members
        $members = [
            ['name' => 'Alice Member', 'email' => 'alice@test.com'],
            ['name' => 'Bob Member',   'email' => 'bob@test.com'],
            ['name' => 'Carol Member', 'email' => 'carol@test.com'],
            ['name' => 'Dave Member',  'email' => 'dave@test.com'],
            ['name' => 'Eve Member',   'email' => 'eve@test.com'],
        ];

        foreach ($members as $member) {
            User::firstOrCreate(
                ['email' => $member['email']],
                [
                    'name' => $member['name'],
                    'password' => Hash::make('password123'),
                    'role' => User::ROLE_MEMBER,
                    'is_active' => true,
                ]
            );
        }

        // Primary test member (referenced in spec)
        User::firstOrCreate(
            ['email' => 'member@test.com'],
            [
                'name' => 'Team Member',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_MEMBER,
                'is_active' => true,
            ]
        );

        $this->command->info('Users seeded: admin, manager, 6 members.');
    }
}
