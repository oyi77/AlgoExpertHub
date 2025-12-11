<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Notifications
 *
 * Endpoints for user notifications.
 */
class NotificationApiController extends Controller
{
    /**
     * List Notifications
     */
    public function index()
    {
        $notifications = Auth::user()->notifications()->latest()->paginate(20);
        return response()->json(['success' => true, 'data' => $notifications]);
    }

    /**
     * Get Unread Count
     */
    public function unreadCount()
    {
        $count = Auth::user()->unreadNotifications()->count();
        return response()->json(['success' => true, 'data' => ['count' => $count]]);
    }

    /**
     * Mark as Read
     */
    public function markAsRead(Request $request)
    {
        if ($request->has('id')) {
            Auth::user()->notifications()->where('id', $request->id)->update(['read_at' => now()]);
        } else {
            Auth::user()->unreadNotifications->markAsRead();
        }

        return response()->json(['success' => true, 'message' => 'Notifications marked as read']);
    }

    /**
     * Delete Notification
     */
    public function destroy($id)
    {
        Auth::user()->notifications()->where('id', $id)->delete();
        return response()->json(['success' => true, 'message' => 'Notification deleted']);
    }
}
