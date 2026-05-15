<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TaskCommentController extends Controller
{
    /**
     * List all comments for a task.
     * GET /api/tasks/{task}/comments
     */
    public function index(Task $task): JsonResponse
    {
        $authUser = auth('api')->user();

        if (! $this->canAccess($authUser, $task)) {
            return $this->error('Access denied.', 403);
        }

        $comments = $task->comments()
            ->with('author:id,name,role')
            ->get();

        return $this->success($comments);
    }

    /**
     * Post a comment on a task.
     * POST /api/tasks/{task}/comments
     */
    public function store(Request $request, Task $task): JsonResponse
    {
        $authUser = auth('api')->user();

        if (! $this->canAccess($authUser, $task)) {
            return $this->error('Access denied.', 403);
        }

        $validated = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $comment = $task->comments()->create([
            'user_id' => $authUser->id,
            'body'    => $validated['body'],
        ]);

        $comment->load('author:id,name,role');

        ActivityLog::record(
            action: 'commented',
            subjectType: Task::class,
            subjectId: $task->id,
            subjectName: $task->title,
            newValues: ['comment_body' => $validated['body']],
        );

        $this->broadcastComment('comment:created', $task->id, [
            'comment' => $comment->toArray(),
            'task_id' => $task->id,
        ]);

        // Notify each @mentioned user via Socket.io
        $this->broadcastMentions($validated['body'], $task, $comment, $authUser);

        return $this->success($comment, 'Comment added.', 201);
    }

    /**
     * Delete a comment (own comment or admin).
     * DELETE /api/tasks/{task}/comments/{comment}
     */
    public function destroy(Task $task, TaskComment $comment): JsonResponse
    {
        $authUser = auth('api')->user();

        if ((int) $comment->task_id !== (int) $task->id) {
            return $this->error('Comment not found on this task.', 404);
        }

        if ($comment->user_id !== $authUser->id && ! $authUser->isAdmin()) {
            return $this->error('You can only delete your own comments.', 403);
        }

        $commentId = $comment->id;
        $comment->delete();

        $this->broadcastComment('comment:deleted', $task->id, [
            'comment_id' => $commentId,
            'task_id'    => $task->id,
        ]);

        return $this->success(null, 'Comment deleted.');
    }

    private function canAccess($authUser, Task $task): bool
    {
        if ($authUser->isAdmin()) {
            return true;
        }

        // Members can comment on tasks assigned to them
        if ($authUser->isMember()) {
            return $task->assigned_to === $authUser->id;
        }

        // Managers must be in the same team
        $team = $task->team;
        return $team && $team->hasMember($authUser);
    }

    private function broadcastMentions(string $body, Task $task, $comment, $authUser): void
    {
        // Skip if body contains no @ at all
        if (!str_contains($body, '@')) {
            Log::info('broadcastMentions: no @ in body', ['task_id' => $task->id]);
            return;
        }

        $nodeUrl       = rtrim(env('NODE_SERVICE_URL', ''), '/');
        $serviceSecret = env('NODE_SERVICE_SECRET', '');
        if (empty($nodeUrl)) {
            Log::warning('broadcastMentions: NODE_SERVICE_URL not set');
            return;
        }

        // Load the task team with its members
        $team = $task->team;
        if (!$team) {
            Log::info('broadcastMentions: task has no team', ['task_id' => $task->id]);
            return;
        }

        $members = $team->members()->get();
        Log::info('broadcastMentions: scanning members', [
            'task_id'      => $task->id,
            'body_excerpt' => mb_substr($body, 0, 80),
            'member_count' => $members->count(),
        ]);

        foreach ($members as $mentioned) {
            // Never notify the author about their own mention
            if ($mentioned->id === $authUser->id) continue;

            // Build a case-insensitive pattern: @Ryan Cordial
            $escapedName = preg_quote($mentioned->name, '/');
            if (!preg_match('/@' . $escapedName . '/iu', $body)) continue;

            Log::info('broadcastMentions: found mention', [
                'mentioned_user_id' => $mentioned->id,
                'mentioned_name'    => $mentioned->name,
            ]);

            try {
                Http::timeout(3)
                    ->withHeaders(['X-Service-Secret' => $serviceSecret])
                    ->post("{$nodeUrl}/api/broadcast", [
                        'event' => 'user:mentioned',
                        'room'  => "user:{$mentioned->id}",
                        'data'  => [
                            'task_id'      => $task->id,
                            'task_title'   => $task->title,
                            'comment_body' => mb_substr($body, 0, 120),
                            'mentioned_by' => $authUser->name,
                        ],
                    ]);
            } catch (\Exception $e) {
                Log::warning('Mention notification failed', ['error' => $e->getMessage()]);
            }
        }
    }

    private function broadcastComment(string $event, int $taskId, array $data): void
    {
        $nodeUrl       = rtrim(env('NODE_SERVICE_URL', ''), '/');
        $serviceSecret = env('NODE_SERVICE_SECRET', '');

        if (empty($nodeUrl)) {
            return;
        }

        try {
            Http::timeout(3)
                ->withHeaders(['X-Service-Secret' => $serviceSecret])
                ->post("{$nodeUrl}/api/broadcast", [
                    'event' => $event,
                    'room'  => "task:{$taskId}",
                    'data'  => $data,
                ]);
        } catch (\Exception $e) {
            Log::warning('Comment broadcast failed', ['task_id' => $taskId, 'error' => $e->getMessage()]);
        }
    }
}
