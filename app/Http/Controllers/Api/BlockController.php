<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class BlockController extends Controller
{
    /**
     * Block a user
     */
    public function block(Request $request, $userId): JsonResponse
    {
        $currentUser = $request->user();

        if ($currentUser->id == $userId) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك حظر نفسك',
            ], 400);
        }

        $targetUser = User::find($userId);
        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم غير موجود',
            ], 404);
        }

        // Check if already blocked
        $alreadyBlocked = Block::where('blocker_id', $currentUser->id)
            ->where('blocked_id', $userId)
            ->exists();

        if ($alreadyBlocked) {
            return response()->json([
                'success' => false,
                'message' => 'هذا المستخدم محظور بالفعل',
            ], 409);
        }

        Block::create([
            'blocker_id' => $currentUser->id,
            'blocked_id' => $userId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم حظر المستخدم بنجاح',
        ]);
    }

    /**
     * Unblock a user
     */
    public function unblock(Request $request, $userId): JsonResponse
    {
        $currentUser = $request->user();

        $block = Block::where('blocker_id', $currentUser->id)
            ->where('blocked_id', $userId)
            ->first();

        if (!$block) {
            return response()->json([
                'success' => false,
                'message' => 'هذا المستخدم غير محظور',
            ], 404);
        }

        $block->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم رفع الحظر بنجاح',
        ]);
    }

    /**
     * Get the list of blocked users
     */
    public function index(Request $request): JsonResponse
    {
        $currentUser = $request->user();

        $blocks = Block::where('blocker_id', $currentUser->id)
            ->with('blocked')
            ->latest()
            ->get()
            ->map(function ($block) {
                $user = $block->blocked;
                return [
                    'id'            => $user->id,
                    'name'          => $user->name,
                    'nickname'      => $user->nickname,
                    'profile_image' => $user->profile_image
                        ? asset(Storage::url($user->profile_image))
                        : null,
                    'blocked_at'    => $block->created_at,
                ];
            });

        return response()->json([
            'success'        => true,
            'blocked_users'  => $blocks,
            'total'          => $blocks->count(),
        ]);
    }
}
