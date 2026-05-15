<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Return a paginated activity log.
     * GET /api/activity-logs
     *
     * Query params: task_id, user_id, action, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $authUser = auth('api')->user();

        $query = ActivityLog::with('actor:id,name,role')
            ->orderBy('created_at', 'desc');

        // Members only see logs for tasks they are assigned to
        if ($authUser->isMember()) {
            $assignedTaskIds = $authUser->assignedTasks()->pluck('id');
            $query->where('subject_type', \App\Models\Task::class)
                ->whereIn('subject_id', $assignedTaskIds);
        }

        // Optional filters
        if ($request->filled('task_id')) {
            $query->where('subject_type', \App\Models\Task::class)
                ->where('subject_id', $request->integer('task_id'));
        }

        if ($request->filled('user_id') && ! $authUser->isMember()) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('subject_name', 'ilike', $term)
                  ->orWhereHas('actor', fn ($a) => $a->where('name', 'ilike', $term));
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
            ],
        ]);
    }
}
