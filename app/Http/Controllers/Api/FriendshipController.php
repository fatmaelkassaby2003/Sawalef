<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use App\Models\User;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FriendshipController extends Controller
{
    /**
     * Send a friend request
     */
    public function sendRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id',
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

            if ($sender->id == $receiverId) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يمكنك إرسال طلب صداقة لنفسك'
                ], 400);
            }

            // Check if a request already exists
            $existingRequest = FriendRequest::where(function ($query) use ($sender, $receiverId) {
                $query->where('sender_id', $sender->id)
                      ->where('receiver_id', $receiverId);
            })->orWhere(function ($query) use ($sender, $receiverId) {
                $query->where('sender_id', $receiverId)
                      ->where('receiver_id', $sender->id);
            })->first();

            if ($existingRequest) {
                if ($existingRequest->status == 'accepted') {
                    return response()->json([
                        'status' => false,
                        'message' => 'أنتم أصدقاء بالفعل'
                    ], 400);
                }
                
                if ($existingRequest->sender_id == $sender->id) {
                    return response()->json([
                        'status' => false,
                        'message' => 'لقد أرسلت طلب صداقة بالفعل'
                    ], 400);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'لديك طلب صداقة معلق من هذا الشخص'
                    ], 400);
                }
            }

            $friendRequest = FriendRequest::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiverId,
                'status' => 'pending',
            ]);

            $receiver = User::find($receiverId);

            return response()->json([
                'status' => true,
                'message' => 'تم إرسال طلب الصداقة بنجاح',
                'data' => [
                    'friend_request_id' => $friendRequest->id,
                    'sender' => [
                        'id' => $sender->id,
                        'name' => $sender->name,
                        'avatar' => $sender->profile_image ? url('storage/' . $sender->profile_image) : null,
                    ],
                    'receiver' => [
                        'id' => $receiver->id,
                        'name' => $receiver->name,
                        'avatar' => $receiver->profile_image ? url('storage/' . $receiver->profile_image) : null,
                    ],
                    'status' => $friendRequest->status,
                    'created_at' => $friendRequest->created_at->toISOString(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إرسال طلب الصداقة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Accept a friend request
     */
    public function acceptRequest(Request $request, FCMService $fcmService)
    {
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $receiver = $request->user();
            $senderId = $request->sender_id;

            $friendRequest = FriendRequest::where('sender_id', $senderId)
                ->where('receiver_id', $receiver->id)
                ->where('status', 'pending')
                ->first();

            if (!$friendRequest) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يوجد طلب صداقة معلق'
                ], 404);
            }

            $friendRequest->update(['status' => 'accepted']);

            // Send notification to the sender
            try {
                $fcmService->sendToUser(
                    $senderId,
                    'تم قبول طلب الصداقة',
                    'وافق ' . $receiver->name . ' على طلب الصداقة الخاص بك. يمكنك الآن التحدث معه.',
                    ['type' => 'friend_request_accepted', 'user_id' => $receiver->id]
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('FCM Error in Friendship: ' . $e->getMessage());
            }

            return response()->json([
                'status' => true,
                'message' => 'تم قبول طلب الصداقة بنجاح'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء قبول طلب الصداقة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Decline a friend request
     */
    public function declineRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $receiver = $request->user();
            $senderId = $request->sender_id;

            $friendRequest = FriendRequest::where('sender_id', $senderId)
                ->where('receiver_id', $receiver->id)
                ->where('status', 'pending')
                ->first();

            if (!$friendRequest) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يوجد طلب صداقة معلق'
                ], 404);
            }

            $friendRequest->delete(); // Or update status to 'declined'

            return response()->json([
                'status' => true,
                'message' => 'تم رفض طلب الصداقة'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء رفض طلب الصداقة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of pending requests
     */
    public function getPendingRequests(Request $request)
    {
        try {
            $user = $request->user();
            $requests = FriendRequest::where('receiver_id', $user->id)
                ->where('status', 'pending')
                ->with('sender')
                ->get()
                ->map(function ($req) {
                    return [
                        'user_id' => $req->sender->id,
                        'name' => $req->sender->name,
                        'avatar' => $req->sender->profile_image ? url('storage/' . $req->sender->profile_image) : null,
                        'created_at' => $req->created_at->diffForHumans(),
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => 'تم جلب طلبات الصداقة المعلقة',
                'data' => $requests
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب طلبات الصداقة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of friends
     */
    public function getFriends(Request $request)
    {
        try {
            $user = $request->user();
            
            $friendIds = FriendRequest::where('status', 'accepted')
                ->where(function ($query) use ($user) {
                    $query->where('sender_id', $user->id)
                          ->orWhere('receiver_id', $user->id);
                })
                ->get()
                ->map(function ($req) use ($user) {
                    return $req->sender_id == $user->id ? $req->receiver_id : $req->sender_id;
                });

            $friends = User::whereIn('id', $friendIds)->get()->map(function($friend) {
                return [
                    'id' => $friend->id,
                    'name' => $friend->name,
                    'avatar' => $friend->profile_image ? url('storage/' . $friend->profile_image) : null,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'تم جلب قائمة الأصدقاء بنجاح',
                'data' => $friends
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب قائمة الأصدقاء',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
