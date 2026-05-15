<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    /**
     * List tasks for a team with optional filters.
     * GET /api/teams/{team}/tasks
     */
    public function index(Request $request, Team $team): JsonResponse
    {
        $authUser = auth('api')->user();

        // Ensure the user belongs to this team (except admin)
        if (! $authUser->isAdmin() && ! $team->hasMember($authUser)) {
            return $this->error('You are not a member of this team.', 403);
        }

        $query = Task::with(['assignedTo:id,name,email', 'createdBy:id,name'])
            ->where('team_id', $team->id);

        // Team members only see tasks assigned to them
        if ($authUser->isMember()) {
            $query->where('assigned_to', $authUser->id);
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $tasks = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $tasks->items(),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ],
        ]);
    }

    /**
     * Create a task within a team.
     * POST /api/teams/{team}/tasks
     */
    public function store(StoreTaskRequest $request, Team $team): JsonResponse
    {
        $authUser = auth('api')->user();

        // Managers can only create tasks in their own teams
        if ($authUser->isManager() && ! $team->hasMember($authUser)) {
            return $this->error('You can only create tasks within your own team.', 403);
        }

        $task = DB::transaction(function () use ($request, $team, $authUser) {
            return Task::create([
                'title' => $request->title,
                'description' => $request->description,
                'status' => Task::STATUS_PENDING,
                'priority' => $request->priority ?? Task::PRIORITY_MEDIUM,
                'assigned_to' => $request->assigned_to,
                'created_by' => $authUser->id,
                'team_id' => $team->id,
                'due_date' => $request->due_date,
            ]);
        });

        $task->load(['assignedTo:id,name,email', 'createdBy:id,name', 'team:id,name']);

        // Notify Node.js service asynchronously (fire & forget)
        if ($task->assigned_to) {
            $this->notifyNodeService($task, 'assigned');
        }

        Log::info('Task created', ['task_id' => $task->id, 'created_by' => $authUser->id]);

        return $this->success($task, 'Task created successfully.', 201);
    }

    /**
     * Get a task's full details.
     * GET /api/tasks/{task}
     */
    public function show(Task $task): JsonResponse
    {
        $authUser = auth('api')->user();

        if (! $this->canAccessTask($authUser, $task)) {
            return $this->error('Access denied.', 403);
        }

        $task->load(['assignedTo:id,name,email', 'createdBy:id,name', 'team:id,name']);

        return $this->success($task);
    }

    /**
     * Update a task's fields.
     * PATCH /api/tasks/{task}
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $authUser = auth('api')->user();

        if (! $this->canModifyTask($authUser, $task)) {
            return $this->error('You do not have permission to edit this task.', 403);
        }

        // Team Members can only update status on their own tasks — not reassign
        if ($authUser->isMember() && $request->filled('assigned_to')) {
            return $this->error('Team Members cannot reassign tasks.', 403);
        }

        $previousAssignee = $task->assigned_to;

        DB::transaction(function () use ($request, $task) {
            $task->update($request->only([
                'title', 'description', 'priority', 'due_date', 'assigned_to',
            ]));
        });

        // Notify if task was reassigned
        if ($request->filled('assigned_to') && $request->assigned_to !== $previousAssignee) {
            $this->notifyNodeService($task->fresh(), 'assigned');
        }

        $task->load(['assignedTo:id,name,email', 'createdBy:id,name']);

        Log::info('Task updated', ['task_id' => $task->id, 'updated_by' => $authUser->id]);

        return $this->success($task, 'Task updated successfully.');
    }

    /**
     * Delete a task.
     * - Admin: any task
     * - Manager: any task within their own team
     * - Member: cannot delete tasks
     * DELETE /api/tasks/{task}
     */
    public function destroy(Task $task): JsonResponse
    {
        $authUser = auth('api')->user();

        // Team Members cannot delete tasks
        if ($authUser->isMember()) {
            return $this->error('Team Members cannot delete tasks.', 403);
        }

        // Managers can only delete tasks within their own teams
        if ($authUser->isManager() && ! $task->team->hasMember($authUser)) {
            return $this->error('Managers can only delete tasks within their own team.', 403);
        }

        $task->delete(); // soft delete

        Log::info('Task deleted', ['task_id' => $task->id, 'deleted_by' => $authUser->id]);

        return $this->success(null, 'Task deleted successfully.');
    }

    /**
     * Update a task's status with transition validation.
     * PATCH /api/tasks/{task}/status
     */
    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse
    {
        $authUser = auth('api')->user();

        if (! $this->canModifyTask($authUser, $task)) {
            return $this->error('You do not have permission to update this task.', 403);
        }

        $newStatus = $request->status;

        if (! $task->canTransitionTo($newStatus)) {
            return $this->error(
                "Invalid status transition from '{$task->status}' to '{$newStatus}'.",
                422
            );
        }

        $task->update(['status' => $newStatus]);

        // Notify Node.js of status change
        $this->notifyNodeService($task->fresh(), 'status_changed');

        Log::info('Task status updated', [
            'task_id' => $task->id,
            'from' => $task->getOriginal('status'),
            'to' => $newStatus,
            'updated_by' => $authUser->id,
        ]);

        return $this->success($task->fresh(), 'Task status updated.');
    }

    /**
     * Archive (hard-delete-eligible) cancelled tasks older than 30 days.
     * Called by Node.js cron job.
     * DELETE /api/tasks/{task}/archive
     */
    public function archive(Task $task): JsonResponse
    {
        if ($task->status !== Task::STATUS_CANCELLED) {
            return $this->error('Only cancelled tasks can be archived.', 422);
        }

        if ($task->updated_at->diffInDays(now()) < 30) {
            return $this->error('Task must be cancelled for at least 30 days before archiving.', 422);
        }

        $task->forceDelete();

        Log::info('Task archived (force deleted)', ['task_id' => $task->id]);

        return $this->success(null, 'Task archived successfully.');
    }

    /**
     * Return tasks due in the next 24 hours (for Node.js cron).
     * GET /api/internal/tasks/upcoming-deadlines
     */
    public function upcomingDeadlines(): JsonResponse
    {
        $tasks = Task::with(['assignedTo:id,name,email', 'team:id,name'])
            ->whereNotIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CANCELLED])
            ->whereNotNull('assigned_to')
            ->whereBetween('due_date', [now(), now()->addHours(24)])
            ->get();

        return $this->success($tasks);
    }

    /**
     * Return incomplete tasks grouped by user (for Node.js daily digest).
     * GET /api/internal/tasks/incomplete-by-user
     */
    public function incompleteByUser(): JsonResponse
    {
        $tasks = Task::with(['assignedTo:id,name,email', 'team:id,name'])
            ->whereIn('status', [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS])
            ->whereNotNull('assigned_to')
            ->get()
            ->groupBy('assigned_to');

        return $this->success($tasks);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function canAccessTask(\App\Models\User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Must be in the same team
        $team = $task->team;
        if (! $team->hasMember($user)) {
            return false;
        }

        // Members can only see their own assigned tasks
        if ($user->isMember() && $task->assigned_to !== $user->id) {
            return false;
        }

        return true;
    }

    private function canModifyTask(\App\Models\User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $team = $task->team;
        if (! $team->hasMember($user)) {
            return false;
        }

        // Team Members can only edit tasks assigned to them
        if ($user->isMember() && $task->assigned_to !== $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Notify the Node.js service about a task event (fire & forget).
     */
    private function notifyNodeService(Task $task, string $eventType): void
    {
        $nodeUrl = rtrim(config('services.node.url', env('NODE_SERVICE_URL', '')), '/');

        if (empty($nodeUrl)) {
            return;
        }

        try {
            Http::timeout(3)->post("{$nodeUrl}/api/notifications/send", [
                'task_id' => $task->id,
                'user_id' => $task->assigned_to,
                'event_type' => $eventType,
                'details' => [
                    'task_title' => $task->title,
                    'task_status' => $task->status,
                    'due_date' => $task->due_date,
                ],
            ]);
        } catch (\Exception $e) {
            // Log but don't fail the main request
            Log::warning('Node.js notification failed', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
