<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    /**
     * Get all conversations for the authenticated user
     */
    public function getConversations(Request $request)
    {
        try {
            $user = $request->user();
            
            $conversations = Conversation::where('user_one_id', $user->id)
                ->orWhere('user_two_id', $user->id)
                ->with(['userOne', 'userTwo', 'latestMessage'])
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(function ($conversation) use ($user) {
                    $otherUser = $conversation->getOtherUser($user->id);
                    
                    return [
                        'id' => $conversation->id,
                        'other_user' => [
                            'id' => $otherUser->id,
                            'name' => $otherUser->name,
                            'avatar' => $otherUser->avatar,
                        ],
                        'latest_message' => $conversation->latestMessage ? [
                            'message' => $conversation->latestMessage->message,
                            'created_at' => $conversation->latestMessage->created_at->diffForHumans(),
                        ] : null,
                        'unread_count' => $conversation->unreadMessagesCount($user->id),
                        'updated_at' => $conversation->updated_at->toISOString(),
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => 'تم جلب المحادثات بنجاح',
                'data' => $conversations
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب المحادثات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get or create a conversation with another user
     */
    public function startConversation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $currentUser = $request->user();
            $otherUserId = $request->user_id;

            if ($currentUser->id == $otherUserId) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يمكنك بدء محادثة مع نفسك'
                ], 400);
            }

            $conversation = Conversation::findOrCreateBetween($currentUser->id, $otherUserId);

            return response()->json([
                'status' => true,
                'message' => 'تم إنشاء المحادثة بنجاح',
                'data' => [
                    'conversation_id' => $conversation->id,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إنشاء المحادثة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get messages for a specific conversation
     */
    public function getMessages(Request $request, $conversationId)
    {
        try {
            $user = $request->user();
            
            $conversation = Conversation::findOrFail($conversationId);

            // Check if user is part of this conversation
            if ($conversation->user_one_id != $user->id && $conversation->user_two_id != $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'غير مصرح لك بالوصول لهذه المحادثة'
                ], 403);
            }

            $messages = Message::where('conversation_id', $conversationId)
                ->with('sender')
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'sender_id' => $message->sender_id,
                        'message' => $message->message,
                        'caption' => $message->caption,
                        'type' => $message->type,
                        'is_read' => $message->is_read,
                        'created_at' => $message->created_at->toISOString(),
                        'sender' => [
                            'id' => $message->sender->id,
                            'name' => $message->sender->name,
                            'avatar' => $message->sender->avatar,
                        ],
                    ];
                });

            // Mark messages as read
            $this->markConversationAsRead($conversation, $user->id);

            return response()->json([
                'status' => true,
                'message' => 'تم جلب الرسائل بنجاح',
                'data' => $messages
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب الرسائل',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a message (text or image)
     */
    public function sendMessage(Request $request, $conversationId)
    {
        // Validate based on what's being sent
        $rules = [];
        
        if ($request->hasFile('image')) {
            $rules['image'] = 'required|image|mimes:jpeg,png,jpg,gif|max:5120'; // Max 5MB
            $rules['message'] = 'nullable|string|max:500'; // Optional caption
        } else {
            $rules['message'] = 'required|string|max:1000';
        }
        
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            $conversation = Conversation::findOrFail($conversationId);

            // Check if user is part of this conversation
            if ($conversation->user_one_id != $user->id && $conversation->user_two_id != $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'غير مصرح لك بإرسال رسائل في هذه المحادثة'
                ], 403);
            }

            $messageContent = '';
            $caption = null;
            $messageType = 'text';

            // Handle image upload if present
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('chat/images'), $imageName);
                $imageUrl = url('chat/images/' . $imageName);
                
                $messageContent = $imageUrl; // Image URL
                $caption = $request->message; // Caption for image
                $messageType = 'image';
            } else {
                $messageContent = $request->message; // Text message
            }

            $message = Message::create([
                'conversation_id' => $conversationId,
                'sender_id' => $user->id,
                'message' => $messageContent,
                'caption' => $caption,
                'type' => $messageType,
            ]);

            // Update conversation timestamp
            $conversation->touch();

            // Load sender relationship
            $message->load('sender');

            // Broadcast the message via Pusher
            broadcast(new MessageSent($message))->toOthers();

            return response()->json([
                'status' => true,
                'message' => $messageType == 'image' ? 'تم إرسال الصورة بنجاح' : 'تم إرسال الرسالة بنجاح',
                'data' => [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'message' => $message->message,
                    'caption' => $message->caption,
                    'type' => $message->type,
                    'created_at' => $message->created_at->toISOString(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إرسال الرسالة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark conversation as read for the current user
     */
    private function markConversationAsRead($conversation, $userId)
    {
        $field = $conversation->user_one_id == $userId ? 'user_one_last_read' : 'user_two_last_read';
        
        $conversation->update([
            $field => now(),
        ]);
    }
}
