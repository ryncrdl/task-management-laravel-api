<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $member;
    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $this->member = User::factory()->create(['role' => 'member', 'is_active' => true]);

        $this->team = Team::factory()->create(['created_by' => $this->admin->id]);

        TeamMember::create(['team_id' => $this->team->id, 'user_id' => $this->admin->id, 'role' => 'lead']);
        TeamMember::create(['team_id' => $this->team->id, 'user_id' => $this->manager->id, 'role' => 'member']);
        TeamMember::create(['team_id' => $this->team->id, 'user_id' => $this->member->id, 'role' => 'member']);
    }

    public function test_admin_can_create_task(): void
    {
        $token = auth('api')->login($this->admin);

        $response = $this->withToken($token)->postJson("/api/teams/{$this->team->id}/tasks", [
            'title' => 'Test Task',
            'description' => 'A test task description',
            'priority' => 'high',
            'assigned_to' => $this->member->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Test Task');

        $this->assertDatabaseHas('tasks', ['title' => 'Test Task', 'team_id' => $this->team->id]);
    }

    public function test_member_cannot_create_task(): void
    {
        $token = auth('api')->login($this->member);

        $response = $this->withToken($token)->postJson("/api/teams/{$this->team->id}/tasks", [
            'title' => 'Unauthorized Task',
        ]);

        $response->assertStatus(403);
    }

    public function test_task_creation_requires_title(): void
    {
        $token = auth('api')->login($this->admin);

        $response = $this->withToken($token)->postJson("/api/teams/{$this->team->id}/tasks", [
            'description' => 'No title given',
        ]);

        $response->assertStatus(422);
    }

    public function test_member_can_only_see_assigned_tasks(): void
    {
        $otherMember = User::factory()->create(['role' => 'member', 'is_active' => true]);
        TeamMember::create(['team_id' => $this->team->id, 'user_id' => $otherMember->id, 'role' => 'member']);

        Task::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->admin->id,
            'assigned_to' => $this->member->id,
        ]);

        Task::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->admin->id,
            'assigned_to' => $otherMember->id,
        ]);

        $token = auth('api')->login($this->member);
        $response = $this->withToken($token)->getJson("/api/teams/{$this->team->id}/tasks");

        $response->assertStatus(200);
        // Member should only see 1 task (assigned to them)
        $this->assertCount(1, $response->json('data'));
    }

    public function test_admin_can_delete_any_task(): void
    {
        $task = Task::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->member->id,
            'assigned_to' => $this->member->id,
        ]);

        $token = auth('api')->login($this->admin);
        $response = $this->withToken($token)->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_member_cannot_delete_task(): void
    {
        $task = Task::factory()->create([
            'team_id' => $this->team->id,
            'created_by' => $this->admin->id,
            'assigned_to' => $this->member->id,
        ]);

        $token = auth('api')->login($this->member);
        $response = $this->withToken($token)->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    }
}
