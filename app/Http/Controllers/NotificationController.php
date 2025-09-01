<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get notifications for the authenticated user
     */
    public function getNotifications(Request $request)
    {
        try {
            /** @var User|null $user */
            $user = Auth::user();
            
            \Log::info('Notifications API called', [
                'user' => $user ? $user->id : 'null'
            ]);
            
            if (!$user) {
                \Log::warning('Unauthorized notifications access');
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $perPage = $request->get('per_page', 15);
            $type = $request->get('type');
            $unreadOnly = $request->get('unread_only', false);

            $query = Notification::where('user_id', $user->id);

            if ($type) {
                $query->where('type', $type);
            }

            if ($unreadOnly) {
                $query->whereNull('read_at');
            }

            $notifications = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);
                
            \Log::info('Notifications retrieved', ['count' => $notifications->count()]);

            $notifications->getCollection()->transform(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'data' => $notification->data,
                    'is_read' => $notification->read_at !== null,
                    'read_at' => $notification->read_at?->format('M d, Y g:i A'),
                    'created_at' => $notification->created_at->format('M d, Y g:i A'),
                    'time_ago' => $notification->created_at->diffForHumans(),
                ];
            });

            return response()->json($notifications);
            
        } catch (\Exception $e) {
            \Log::error('Notifications API error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount()
    {
        try {
            /** @var User|null $user */
            $user = Auth::user();
            
            \Log::info('Unread count API called', [
                'user' => $user ? $user->id : 'null'
            ]);
            
            if (!$user) {
                \Log::warning('Unauthorized unread count access');
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $count = Notification::where('user_id', $user->id)
                ->whereNull('read_at')
                ->count();
                
            \Log::info('Unread count retrieved', ['count' => $count]);

            return response()->json(['unread_count' => $count]);
            
        } catch (\Exception $e) {
            \Log::error('Unread count API error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId)
    {
        /** @var User|null $user */
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $notification = Notification::where('user_id', $user->id)
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        /** @var User|null $user */
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    /**
     * Delete notification
     */
    public function deleteNotification($notificationId)
    {
        /** @var User|null $user */
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $notification = Notification::where('user_id', $user->id)
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }

        $notification->delete();

        return response()->json(['message' => 'Notification deleted']);
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats()
    {
        /** @var User|null $user */
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $totalCount = Notification::where('user_id', $user->id)->count();
        $unreadCount = Notification::where('user_id', $user->id)->whereNull('read_at')->count();
        
        $typeStats = Notification::where('user_id', $user->id)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return response()->json([
            'total_notifications' => $totalCount,
            'unread_notifications' => $unreadCount,
            'read_notifications' => $totalCount - $unreadCount,
            'by_type' => $typeStats,
        ]);
    }

    /**
     * Create notification (for internal use)
     */
    public function createNotification(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:booking_confirmation,reminder,status_update,promotion',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'data' => 'nullable|array',
        ]);

        $notification = Notification::create($request->all());

        return response()->json([
            'message' => 'Notification created successfully',
            'notification' => $notification,
        ], 201);
    }
}