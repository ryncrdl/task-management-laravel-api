<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationJobController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────
    // Internal endpoints — called by Node.js (X-Service-Secret auth)
    // ──────────────────────────────────────────────────────────────────────

    /**
     * POST /api/internal/jobs
     * Enqueue a new notification job.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'task_id'    => 'nullable|integer',
            'user_id'    => 'nullable|integer',
            'event_type' => 'required|string|max:50',
            'details'    => 'nullable|array',
        ]);

        $job = NotificationJob::create([
            'task_id'    => $data['task_id'] ?? null,
            'user_id'    => $data['user_id'] ?? null,
            'event_type' => $data['event_type'],
            'details'    => $data['details'] ?? [],
            'status'     => 'pending',
            'created_at' => now(),
        ]);

        return response()->json(['id' => $job->id, 'status' => 'pending'], 201);
    }

    /**
     * GET /api/internal/jobs/pending
     * Claim and return up to 20 pending jobs (atomic update to 'processing').
     */
    public function claimPending(): JsonResponse
    {
        $staleThreshold = now()->subMinutes(2);

        // Pick up pending jobs AND jobs stuck in 'processing' for >2 minutes
        $ids = NotificationJob::where(function ($q) use ($staleThreshold) {
                $q->where('status', 'pending')
                  ->orWhere(function ($q2) use ($staleThreshold) {
                      $q2->where('status', 'processing')
                         ->where('updated_at', '<', $staleThreshold);
                  });
            })
            ->orderBy('scheduled_at')
            ->limit(20)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return response()->json(['data' => []]);
        }

        NotificationJob::whereIn('id', $ids)->update(['status' => 'processing', 'updated_at' => now()]);

        $jobs = NotificationJob::whereIn('id', $ids)->get();

        return response()->json(['data' => $jobs]);
    }

    /**
     * PATCH /api/internal/jobs/{id}
     * Update a job's status after processing (sent / failed).
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status'        => 'required|in:sent,failed,pending',
            'error_message' => 'nullable|string',
        ]);

        $job = NotificationJob::findOrFail($id);

        $job->increment('attempts');

        $job->update([
            'status'        => $data['status'],
            'error_message' => $data['error_message'] ?? null,
            'processed_at'  => now(),
        ]);

        return response()->json(['id' => $job->id, 'status' => $job->status, 'attempts' => $job->attempts]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Admin endpoints — called by React (JWT auth, admin role)
    // ──────────────────────────────────────────────────────────────────────

    /**
     * GET /api/admin/notification-jobs
     * Paginated list with optional status filter.
     */
    public function index(Request $request): JsonResponse
    {
        $query = NotificationJob::orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $jobs = $query->paginate($request->integer('per_page', 20));

        $enriched = array_map(function ($job) {
            $data    = $job->toArray();
            $details = is_array($job->details) ? $job->details : [];
            $data['recipient_name']  = $details['assigned_to_name']  ?? null;
            $data['recipient_email'] = $details['assigned_to_email'] ?? null;
            return $data;
        }, $jobs->items());

        return response()->json([
            'data' => $enriched,
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page'    => $jobs->lastPage(),
                'per_page'     => $jobs->perPage(),
                'total'        => $jobs->total(),
            ],
        ]);
    }

    /**
     * GET /api/admin/notification-jobs/stats
     * Counts per status.
     */
    public function stats(): JsonResponse
    {
        $rows = NotificationJob::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'stats' => [
                'pending'    => (int) ($rows['pending']    ?? 0),
                'processing' => (int) ($rows['processing'] ?? 0),
                'sent'       => (int) ($rows['sent']       ?? 0),
                'failed'     => (int) ($rows['failed']     ?? 0),
            ],
        ]);
    }

    /**
     * POST /api/admin/notification-jobs/{id}/retry
     * Reset a failed job back to pending.
     */
    public function retry(int $id): JsonResponse
    {
        $job = NotificationJob::where('id', $id)->where('status', 'failed')->firstOrFail();

        $job->update([
            'status'        => 'pending',
            'attempts'      => 0,
            'error_message' => null,
            'scheduled_at'  => now(),
        ]);

        return response()->json(['message' => 'Job queued for retry.', 'id' => $job->id]);
    }

    /**
     * DELETE /api/admin/notification-jobs/{id}
     * Remove a sent or failed job.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = NotificationJob::where('id', $id)
            ->whereIn('status', ['sent', 'failed'])
            ->delete();

        if (! $deleted) {
            return response()->json(['message' => 'Job not found or still pending/processing.'], 404);
        }

        return response()->json(['message' => 'Job deleted.']);
    }
}
