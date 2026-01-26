<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FCMService;
use App\Models\SiteNotification;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    protected $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Update FCM Token for the user
     */
    public function updateToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false, 
                'message' => 'Token required',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $user->update(['fcm_token' => $request->fcm_token]);

            return response()->json([
                'status' => true,
                'message' => 'Token updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error updating token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send Test Notification (Admin or Dev usage)
     */
    public function sendTest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'body' => 'required|string',
            'user_id' => 'nullable|exists:users,id', // Optional, if not provided sends to current user
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $userId = $request->user_id ?? $request->user()->id;
        
        $success = $this->fcmService->sendToUser(
            $userId, 
            $request->title, 
            $request->body,
            ['type' => 'test_notification']
        );

        if ($success) {
            return response()->json(['status' => true, 'message' => 'Notification sent successfully']);
        } else {
            return response()->json(['status' => false, 'message' => 'Failed to send notification'], 500);
        }
    }

    /**
     * Get All Notifications for the user
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $notifications = SiteNotification::where('user_id', $user->id)
                ->orWhereNull('user_id')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'status' => true,
                'message' => 'Notifications retrieved successfully',
                'data' => $notifications
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get Latest 5 Notifications
     */
    public function latest(Request $request)
    {
        try {
            $user = $request->user();
            $notifications = SiteNotification::where('user_id', $user->id)
                ->orWhereNull('user_id')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Latest notifications retrieved successfully',
                'data' => $notifications
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get Unread Notifications Count
     */
    public function unreadCount(Request $request)
    {
        try {
            $user = $request->user();
            $count = SiteNotification::where(function($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->orWhereNull('user_id');
                })
                ->where('status', '!=', 'read')
                ->count();

            return response()->json([
                'status' => true,
                'message' => 'Unread count retrieved',
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mark a specific notification as read
     */
    public function markAsRead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|exists:site_notifications,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $user = $request->user();
            $notification = SiteNotification::where('id', $request->notification_id)
                ->where(function($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->orWhereNull('user_id');
                })
                ->first();

            if ($notification) {
                $notification->update(['status' => 'read']);
                return response()->json(['status' => true, 'message' => 'Notification marked as read']);
            }

            return response()->json(['status' => false, 'message' => 'Notification not found or unauthorized'], 404);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mark all user notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $user = $request->user();
            SiteNotification::where('user_id', $user->id)
                ->where('status', '!=', 'read')
                ->update(['status' => 'read']);

            return response()->json(['status' => true, 'message' => 'All notifications marked as read']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
