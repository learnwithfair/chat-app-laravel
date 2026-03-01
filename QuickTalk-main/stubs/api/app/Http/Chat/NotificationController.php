<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * get user's notifications.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function getNotifications(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthorized', 401);
        }

        $data = $user->notifications()
            ->with('notifiable')
            ->latest()
            ->get()
            ->map(function ($notification) {
                return [
                    'id'          => $notification->id,
                    'data'        => $notification->data,
                    'read_at'     => $notification->read_at,
                    'created_at'  => $notification->created_at,
                    'user_name'   => $notification->notifiable?->name,
                    'user_avatar' => $notification->notifiable?->avatar,
                ];
            });

        if ($data->isEmpty()) {
            return $this->error([], 'No Notifications Found', 200);
        }

        return $this->success($data, 'Notifications fetched successfully', 200);
    }

    /**
     * Mark notifications as read.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function allMarkAsRead(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthorized', 401);
        }

        $user->unreadNotifications->markAsRead();

        return $this->success([], 'All notifications marked as read', 200);
    }

    /**
     * Mark a specific notification as read.
     *
     * @param Request $request
     * @param string $notification_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request, $notification_id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthorized', 401);
        }

        $notification = $user->notifications()->where('id', $notification_id)->first();

        if (!$notification) {
            return $this->error([], 'Notification not found', 404);
        }

        if ($notification->read_at) {
            return $this->error([], 'Notification already marked as read', 400);
        }

        $notification->markAsRead();

        return $this->success([], 'Notification marked as read', 200);
    }

    /**
     * Mark a specific notification as unread.
     *
     * @param Request $request
     * @param string $notification_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsUnread(Request $request, $notification_id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error([], 'Unauthorized', 401);
        }

        $notification = $user->notifications()->where('id', $notification_id)->first();

        if (!$notification) {
            return $this->error([], 'Notification not found', 404);
        }

        if (!$notification->read_at) {
            return $this->error([], 'Notification already marked as unread', 400);
        }

        $notification->update(['read_at' => null]);

        return $this->success([], 'Notification marked as unread', 200);
    }
}
