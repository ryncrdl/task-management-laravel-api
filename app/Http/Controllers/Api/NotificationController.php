<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * GET /api/notifications
     * Returns the latest 50 notifications for the authenticated user.
     */
    public function index(): JsonResponse
    {
        $user = auth('api')->user();

        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return $this->success($notifications);
    }

    /**
     * POST /api/notifications/mark-all-read
     * Marks every notification for the authenticated user as read.
     */
    public function markAllRead(): JsonResponse
    {
        $user = auth('api')->user();

        Notification::where('user_id', $user->id)
            ->where('read', false)
            ->update(['read' => true]);

        return $this->success(null, 'All notifications marked as read.');
    }

    /**
     * DELETE /api/notifications
     * Deletes all notifications for the authenticated user.
     */
    public function clearAll(): JsonResponse
    {
        $user = auth('api')->user();

        Notification::where('user_id', $user->id)->delete();

        return $this->success(null, 'Notifications cleared.');
    }
}
