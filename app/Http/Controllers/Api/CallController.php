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

            // 3. Send FCM Incoming Call Notification
            $notifData = [
                'type' => 'incoming_call',
                'call_type' => $request->type,
                'channel_name' => $channelName,
                'caller_id' => (string) $sender->id,
                'caller_name' => $sender->name,
                'caller_avatar' => $sender->profile_image ? url('storage/' . $sender->profile_image) : null,
            ];

            $fcmService->sendToUser(
                $receiverId,
                'اتصال وارد',
                'لديك مكالمة ' . ($request->type == 'voice' ? 'صوتية' : 'فيديو') . ' من ' . $sender->name,
                $notifData
            );

            // 4. Log call in database
            $call = Call::create([
                'caller_id' => $sender->id,
                'receiver_id' => $receiverId,
                'channel_name' => $channelName,
                'type' => $request->type,
                'status' => 'ringing',
            ]);

            // 5. Broadcast via Pusher (Real-time)
            try {
                $broadcastData = array_merge($notifData, ['call_id' => $call->id, 'receiver_id' => $receiverId]);
                broadcast(new CallEvent($broadcastData))->toOthers();
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
    public function acceptCall(Request $request, AgoraTokenService $agoraService)
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

        if ($call) {
            $call->update([
                'status' => 'accepted',
                'started_at' => now()
            ]);
        }

        $token = $agoraService->createToken($request->channel_name, $request->user()->id);

        return response()->json([
            'status' => true,
            'message' => 'تم قبول المكالمة',
            'data' => [
                'agora_token' => $token,
                'app_id' => config('services.agora.app_id'),
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

            // Notify the other party via FCM
            $otherUserId = ($call->caller_id == $user->id) ? $call->receiver_id : $call->caller_id;
            
            $fcmService->sendToUser($otherUserId, 'انتهت المكالمة', '', [
                'type' => 'call_ended',
                'channel_name' => $request->channel_name,
                'reason' => $request->reason ?? 'ended'
            ]);

            // Notify via Pusher (Real-time)
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
