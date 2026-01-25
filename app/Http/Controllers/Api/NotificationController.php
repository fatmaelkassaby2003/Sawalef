<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FCMService;
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
}
