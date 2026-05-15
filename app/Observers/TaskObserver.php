<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Task;

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
                'assigned_to' => $task->assigned_to,
            ],
        );
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
            $old[$field] = $task->getOriginal($field);
            $new[$field] = $newValue;
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
