<?php

namespace Tests\Unit;

use App\Models\Task;
use PHPUnit\Framework\TestCase;

class TaskStatusTransitionTest extends TestCase
{
    private function makeTask(string $status): Task
    {
        $task = new Task();
        $task->status = $status;
        return $task;
    }

    // ─── Valid transitions ────────────────────────────────────────────────────

    public function test_pending_can_transition_to_in_progress(): void
    {
        $task = $this->makeTask(Task::STATUS_PENDING);
        $this->assertTrue($task->canTransitionTo(Task::STATUS_IN_PROGRESS));
    }

    public function test_pending_can_transition_to_cancelled(): void
    {
        $task = $this->makeTask(Task::STATUS_PENDING);
        $this->assertTrue($task->canTransitionTo(Task::STATUS_CANCELLED));
    }

    public function test_in_progress_can_transition_to_completed(): void
    {
        $task = $this->makeTask(Task::STATUS_IN_PROGRESS);
        $this->assertTrue($task->canTransitionTo(Task::STATUS_COMPLETED));
    }

    public function test_in_progress_can_transition_back_to_pending(): void
    {
        $task = $this->makeTask(Task::STATUS_IN_PROGRESS);
        $this->assertTrue($task->canTransitionTo(Task::STATUS_PENDING));
    }

    // ─── Invalid transitions ──────────────────────────────────────────────────

    public function test_completed_is_a_terminal_state(): void
    {
        $task = $this->makeTask(Task::STATUS_COMPLETED);
        $this->assertFalse($task->canTransitionTo(Task::STATUS_PENDING));
        $this->assertFalse($task->canTransitionTo(Task::STATUS_IN_PROGRESS));
        $this->assertFalse($task->canTransitionTo(Task::STATUS_CANCELLED));
        $this->assertTrue($task->isTerminal());
    }

    public function test_cancelled_is_a_terminal_state(): void
    {
        $task = $this->makeTask(Task::STATUS_CANCELLED);
        $this->assertFalse($task->canTransitionTo(Task::STATUS_PENDING));
        $this->assertFalse($task->canTransitionTo(Task::STATUS_IN_PROGRESS));
        $this->assertFalse($task->canTransitionTo(Task::STATUS_COMPLETED));
        $this->assertTrue($task->isTerminal());
    }

    public function test_pending_cannot_jump_directly_to_completed(): void
    {
        $task = $this->makeTask(Task::STATUS_PENDING);
        $this->assertFalse($task->canTransitionTo(Task::STATUS_COMPLETED));
    }

    public function test_in_progress_cannot_transition_to_cancelled(): void
    {
        $task = $this->makeTask(Task::STATUS_IN_PROGRESS);
        $this->assertFalse($task->canTransitionTo(Task::STATUS_CANCELLED));
    }

    public function test_invalid_target_status_returns_false(): void
    {
        $task = $this->makeTask(Task::STATUS_PENDING);
        $this->assertFalse($task->canTransitionTo('not_a_real_status'));
    }
}
