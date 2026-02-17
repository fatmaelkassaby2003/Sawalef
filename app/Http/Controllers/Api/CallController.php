<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Call;
use App\Events\CallEvent;
use App\Services\FCMService;
use App\Services\AgoraTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CallController extends Controller
{
    /**
     * Start a call - sends notification to the receiver
     */
    public function startCall(Request $request, FCMService $fcmService, AgoraTokenService $agoraService)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id',
            'type' => 'required|in:voice,video',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $sender = $request->user();
            $receiverId = $request->receiver_id;

            // 1. Check if friends
            if (!$sender->isFriendWith($receiverId)) {
                return response()->json([
                    'status' => false,
                    'message' => 'يمكنك الاتصال بالأصدقاء فقط'
                ], 403);
            }

            // 2. Generate a unique channel name for this pair
            $channelName = 'call_' . min($sender->id, $receiverId) . '_' . max($sender->id, $receiverId);

            // 3. Log call in database first
            $call = Call::create([
                'caller_id' => $sender->id,
                'receiver_id' => $receiverId,
                'channel_name' => $channelName,
                'type' => $request->type,
                'status' => 'ringing',
            ]);

            // 4. Get receiver data
            $receiver = User::find($receiverId);

            // 5. Prepare caller and receiver data
            $callerData = [
                'id' => $sender->id,
                'name' => $sender->name,
                'nickname' => $sender->nickname,
                'avatar' => $sender->profile_image ? url('storage/' . $sender->profile_image) : null,
            ];

            $receiverData = null;
            if ($receiver) {
                $receiverData = [
                    'id' => $receiver->id,
                    'name' => $receiver->name,
                    'nickname' => $receiver->nickname,
                    'avatar' => $receiver->profile_image ? url('storage/' . $receiver->profile_image) : null,
                ];
            }

            // 6. Generate Agora token for receiver
            $receiverToken = $agoraService->createToken($channelName, $receiverId, ($request->type == 'voice'));
            $appId = config('services.agora.app_id');

            // 7. Send FCM Incoming Call Notification
            // Send arrays directly - FCMService will handle encoding for FCM
            $notifData = [
                'type' => 'incoming_call',
                'call_id' => (string) $call->id,
                'call_type' => $call->type,
                'channel_name' => $call->channel_name,
                'status' => $call->status,
                'created_at' => $call->created_at->toIso8601String(),
                'agora_token' => $receiverToken, // Token for receiver
                'app_id' => $appId, // Agora App ID
                'caller_id' => (string) $sender->id, // Caller ID
                'receiver_id' => (string) $receiverId, // Receiver ID
                'user_id' => $receiverId, // Add user_id to prevent duplicate notifications
                'caller' => $callerData, // Send as array, not JSON string
            ];

            if ($receiverData) {
                $notifData['receiver'] = $receiverData; // Send as array, not JSON string
            }

            // Send FCM notification (don't block on failure)
            try {
                $fcmService->sendToUser(
                    $receiverId,
                    'اتصال وارد',
                    'لديك مكالمة ' . ($request->type == 'voice' ? 'صوتية' : 'فيديو') . ' من ' . $sender->name,
                    $notifData
                );
            } catch (\Exception $e) {
                Log::error('FCM Send Error in Call: ' . $e->getMessage());
            }

            // 7. Broadcast via Pusher (Real-time) - Here we can use objects
            $pusherData = [
                'type' => 'incoming_call',
                'call_id' => $call->id,
                'call_type' => $call->type,
                'channel_name' => $call->channel_name,
                'status' => $call->status,
                'created_at' => $call->created_at->toIso8601String(),
                'caller' => $callerData,
                'receiver_id' => $receiverId, // Add receiver_id for Pusher
            ];
            
            if ($receiverData) {
                $pusherData['receiver'] = $receiverData;
            }

            try {
                broadcast(new CallEvent($pusherData))->toOthers();
            } catch (\Exception $e) {
                Log::error('Pusher Broadcast Error: ' . $e->getMessage());
            }

            // 6. Return token
            $token = $agoraService->createToken($channelName, $sender->id, ($request->type == 'voice'));

            return response()->json([
                'status' => true,
                'message' => 'تم بدء المكالمة وإشعار الطرف الآخر',
                'data' => [
                    'call_id' => $call->id,
                    'channel_name' => $channelName,
                    'agora_token' => $token,
                    'app_id' => config('services.agora.app_id'),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Call Start Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء بدء المكالمة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Accept a call - generate token for the receiver
     */
    /**
     * Accept a call - Updates status and notifies caller (Step 1)
     */
    public function acceptCall(Request $request, FCMService $fcmService)
    {
        $validator = Validator::make($request->all(), [
            'channel_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        // Update call status
        $call = Call::where('channel_name', $request->channel_name)
            ->where('status', 'ringing')
            ->first();

        if (!$call) {
            return response()->json([
                'status' => false,
                'message' => 'المكالمة غير موجودة أو انتهت صلاحيتها'
            ], 404);
        }

        $call->update([
            'status' => 'accepted',
        ]);

        // Broadcast via Pusher only (no FCM notification)
        try {
            broadcast(new CallEvent([
                'type' => 'call_accepted',
                'channel_name' => $request->channel_name,
                'receiver_id' => $request->user()->id
            ]))->toOthers();
        } catch (\Exception $e) {
            Log::error('Pusher Broadcast Error: ' . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'تم قبول الدعوة، في انتظار تأكيد المتصل',
            'data' => [
                'status' => 'waiting_for_confirmation',
                'channel_name' => $request->channel_name
            ]
        ], 200);
    }

    /**
     * Confirm a call - Starts the call and issues tokens (Step 2)
     */
    public function confirmCall(Request $request, AgoraTokenService $agoraService)
    {
        $validator = Validator::make($request->all(), [
            'channel_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $call = Call::where('channel_name', $request->channel_name)
            ->where('status', 'accepted') // Must be in this state
            ->first();

        if (!$call) {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكن بدء المكالمة (ربما لم يقبل الطرف الآخر بعد)'
            ], 400);
        }

        // Update status to active (we'll use 'ended' temporarily until we add 'active' to enum)
        // For now, we keep it as 'accepted' and track via started_at
        $call->update([
            'started_at' => now()
        ]);

        $isVoice = ($call->type == 'voice');

        // Generate tokens for both Caller (Me) and Receiver
        $callerToken = $agoraService->createToken($request->channel_name, $call->caller_id, $isVoice);
        $receiverToken = $agoraService->createToken($request->channel_name, $call->receiver_id, $isVoice);

        // Broadcast START event to Receiver with their token
        try {
            broadcast(new CallEvent([
                'type' => 'call_started',
                'channel_name' => $request->channel_name,
                'agora_token' => $receiverToken,
                'app_id' => config('services.agora.app_id'),
                'caller_id' => $call->caller_id
            ]))->toOthers();
        } catch (\Exception $e) {
            Log::error('Pusher Broadcast Error: ' . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'تم بدء المكالمة بنجاح',
            'data' => [
                'agora_token' => $callerToken,
                'app_id' => config('services.agora.app_id'),
                'channel_name' => $request->channel_name,
            ]
        ], 200);
    }

    /**
     * Decline or End a call - notifies the other party
     */
    public function endCall(Request $request, FCMService $fcmService)
    {
        $validator = Validator::make($request->all(), [
            'channel_name' => 'required|string',
            'reason' => 'nullable|string', // e.g., 'busy', 'declined', 'ended'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        
        $call = Call::where('channel_name', $request->channel_name)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($call) {
            $status = ($request->reason == 'declined') ? 'declined' : 'ended';
            
            $updateData = ['status' => $status];
            
            if ($call->status == 'accepted' && $call->started_at) {
                $updateData['ended_at'] = now();
                $updateData['duration'] = now()->diffInSeconds($call->started_at);
            }
            
            if ($call->status == 'ringing' && $request->reason != 'declined') {
                $updateData['status'] = 'missed';
            }

            $call->update($updateData);

            // Notify the other party via Pusher only (no FCM notification)
            $otherUserId = ($call->caller_id == $user->id) ? $call->receiver_id : $call->caller_id;
            
            try {
                broadcast(new CallEvent([
                    'type' => 'call_ended',
                    'receiver_id' => $otherUserId,
                    'channel_name' => $request->channel_name,
                    'reason' => $request->reason ?? 'ended'
                ]))->toOthers();
            } catch (\Exception $e) {
                Log::error('Pusher End Call Error: ' . $e->getMessage());
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'تم إنهاء المكالمة'
        ]);
    }

    /**
     * Get user call history
     */
    public function getHistory(Request $request)
    {
        $user = $request->user();
        $history = Call::where('caller_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->with(['caller:id,name,profile_image', 'receiver:id,name,profile_image'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => true,
            'message' => 'تم جلب سجل المكالمات بنجاح',
            'data' => $history
        ]);
    }
}
