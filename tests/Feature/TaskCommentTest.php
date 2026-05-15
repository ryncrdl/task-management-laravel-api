<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCommentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $member;
    private Team $team;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin   = User::factory()->create(['role' => 'admin',  'is_active' => true]);
        $this->member  = User::factory()->create(['role' => 'member', 'is_active' => true]);

        $this->team = Team::factory()->create(['created_by' => $this->admin->id]);
        TeamMember::create(['team_id' => $this->team->id, 'user_id' => $this->admin->id,  'role' => 'lead']);
        TeamMember::create(['team_id' => $this->team->id, 'user_id' => $this->member->id, 'role' => 'member']);

        $this->task = Task::factory()->create([
            'team_id'     => $this->team->id,
            'created_by'  => $this->admin->id,
            'assigned_to' => $this->member->id,
        ]);
    }

    public function test_team_member_can_post_comment(): void
    {
        $token = auth('api')->login($this->member);

        $response = $this->withToken($token)
            ->postJson("/api/tasks/{$this->task->id}/comments", ['body' => 'This is a comment.']);

        $response->assertStatus(201)
            ->assertJsonPath('data.body', 'This is a comment.');

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $this->task->id,
            'user_id' => $this->member->id,
        ]);
    }

    public function test_comment_body_is_required(): void
    {
        $token = auth('api')->login($this->admin);

        $this->withToken($token)
            ->postJson("/api/tasks/{$this->task->id}/comments", [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['body']]);
    }

    public function test_member_can_delete_own_comment(): void
    {
        $comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->member->id,
            'body'    => 'To be deleted.',
        ]);

        $token = auth('api')->login($this->member);

        $this->withToken($token)
            ->deleteJson("/api/tasks/{$this->task->id}/comments/{$comment->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('task_comments', ['id' => $comment->id]);
    }

    public function test_member_cannot_delete_others_comment(): void
    {
        $other   = User::factory()->create(['role' => 'member', 'is_active' => true]);
        TeamMember::create(['team_id' => $this->team->id, 'user_id' => $other->id, 'role' => 'member']);

        $comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->admin->id,
            'body'    => 'Admin comment.',
        ]);

        $token = auth('api')->login($other);

        $this->withToken($token)
            ->deleteJson("/api/tasks/{$this->task->id}/comments/{$comment->id}")
            ->assertStatus(403);
    }

    public function test_admin_can_list_task_comments(): void
    {
        TaskComment::factory()->count(3)->create([
            'task_id' => $this->task->id,
            'user_id' => $this->admin->id,
        ]);

        $token = auth('api')->login($this->admin);

        $this->withToken($token)
            ->getJson("/api/tasks/{$this->task->id}/comments")
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}
