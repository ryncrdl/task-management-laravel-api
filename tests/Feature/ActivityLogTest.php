<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $member;
    private Team $team;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin   = User::factory()->create(['role' => 'admin',   'is_active' => true]);
        $this->manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $this->member  = User::factory()->create(['role' => 'member',  'is_active' => true]);

        $this->team = Team::factory()->create(['created_by' => $this->admin->id]);
        TeamMember::create(['team_id' => $this->team->id, 'user_id' => $this->admin->id,   'role' => 'lead']);
        TeamMember::create(['team_id' => $this->team->id, 'user_id' => $this->manager->id, 'role' => 'member']);
        TeamMember::create(['team_id' => $this->team->id, 'user_id' => $this->member->id,  'role' => 'member']);

        $this->task = Task::factory()->create([
            'team_id'     => $this->team->id,
            'created_by'  => $this->admin->id,
            'assigned_to' => $this->member->id,
        ]);
    }

    public function test_task_creation_generates_activity_log(): void
    {
        $token = auth('api')->login($this->admin);

        $this->withToken($token)->postJson("/api/teams/{$this->team->id}/tasks", [
            'title'       => 'Logged Task',
            'priority'    => 'medium',
            'assigned_to' => $this->member->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action'       => 'created',
            'subject_type' => Task::class,
            'subject_name' => 'Logged Task',
        ]);
    }

    public function test_task_status_change_generates_status_changed_log(): void
    {
        $token = auth('api')->login($this->admin);

        $this->withToken($token)->patchJson("/api/tasks/{$this->task->id}/status", [
            'status' => 'in_progress',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action'      => 'status_changed',
            'subject_id'  => $this->task->id,
        ]);
    }

    public function test_admin_can_list_activity_logs(): void
    {
        ActivityLog::record('created', Task::class, $this->task->id, $this->task->title);

        $token = auth('api')->login($this->admin);

        $this->withToken($token)
            ->getJson('/api/activity-logs')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_member_only_sees_own_task_logs(): void
    {
        $otherTask = Task::factory()->create([
            'team_id'     => $this->team->id,
            'created_by'  => $this->admin->id,
            'assigned_to' => $this->manager->id,
        ]);

        ActivityLog::create([
            'user_id'      => $this->admin->id,
            'action'       => 'created',
            'subject_type' => Task::class,
            'subject_id'   => $this->task->id,
            'subject_name' => $this->task->title,
            'created_at'   => now(),
        ]);

        ActivityLog::create([
            'user_id'      => $this->admin->id,
            'action'       => 'created',
            'subject_type' => Task::class,
            'subject_id'   => $otherTask->id,
            'subject_name' => $otherTask->title,
            'created_at'   => now(),
        ]);

        $token = auth('api')->login($this->member);

        $response = $this->withToken($token)->getJson('/api/activity-logs');
        $response->assertStatus(200);

        // Member should only see the log for the task assigned to them
        foreach ($response->json('data') as $log) {
            $this->assertEquals($this->task->id, $log['subject_id']);
        }
    }
}
