<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffNotificationController extends Controller
{
    /**
     * List notifications for the authenticated staff member.
     */
    public function index(Request $request): JsonResponse
    {
        $staff = $request->user();

        $notifications = $staff->notifications()
            ->latest()
            ->take($request->integer('limit', 30))
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->data['type'] ?? 'unknown',
                'message' => $n->data['message'] ?? '',
                'source' => $n->data['source'] ?? null,
                'guest_name' => $n->data['guest_name'] ?? null,
                'order_number' => $n->data['order_number'] ?? null,
                'table_name' => $n->data['table_name'] ?? null,
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at->toISOString(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $staff->unreadNotifications()->count(),
            ],
            'message' => 'Notifications retrieved',
        ]);
    }

    /**
     * Get unread notification count (for badge).
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ],
            'message' => 'Unread count retrieved',
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->find($id);

        if (! $notification) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'All notifications marked as read',
        ]);
    }
}
