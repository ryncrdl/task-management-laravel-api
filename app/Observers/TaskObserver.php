<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\User;

class TaskObserver
{
    /** Fields we track for change detection. */
    private const TRACKED = ['title', 'description', 'status', 'priority', 'assigned_to', 'due_date'];

    public function created(Task $task): void
    {
        ActivityLog::record(
            action: 'created',
            subjectType: Task::class,
            subjectId: $task->id,
            subjectName: $task->title,
            newValues: [
                'title'       => $task->title,
                'status'      => $task->status,
                'priority'    => $task->priority,
                'assigned_to' => $task->assigned_to
                    ? (User::find($task->assigned_to)?->name ?? $task->assigned_to)
                    : null,
            ],
        );
    }

    /** Resolve a user ID to a display name, or return the raw value if not a user field. */
    private function resolveValue(string $field, mixed $value): mixed
    {
        if ($field === 'assigned_to' && $value !== null) {
            return User::find($value)?->name ?? $value;
        }
        return $value;
    }

    public function updated(Task $task): void
    {
        $dirty = array_intersect_key($task->getDirty(), array_flip(self::TRACKED));

        if (empty($dirty)) {
            return;
        }

        $old = [];
        $new = [];
        foreach ($dirty as $field => $newValue) {
            $old[$field] = $this->resolveValue($field, $task->getOriginal($field));
            $new[$field] = $this->resolveValue($field, $newValue);
        }

        $action = array_key_exists('status', $dirty) ? 'status_changed' : 'updated';

        ActivityLog::record(
            action: $action,
            subjectType: Task::class,
            subjectId: $task->id,
            subjectName: $task->title,
            oldValues: $old,
            newValues: $new,
        );
    }

    public function deleted(Task $task): void
    {
        ActivityLog::record(
            action: 'deleted',
            subjectType: Task::class,
            subjectId: $task->id,
            subjectName: $task->title,
            oldValues: ['title' => $task->title, 'status' => $task->status],
        );
    }
}
