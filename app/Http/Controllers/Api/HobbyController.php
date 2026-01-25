<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hobby;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class HobbyController extends Controller
{
    /**
     * List all available hobbies
     */
    public function index(): JsonResponse
    {
        $hobbies = Hobby::all(['id', 'name', 'icon'])->map(function($hobby) {
            return [
                'id' => $hobby->id,
                'name' => $hobby->name,
                'icon' => $hobby->icon ? asset($hobby->icon) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'hobbies' => $hobbies,
        ]);
    }

    /**
     * Get authenticated user's hobbies
     */
    public function myHobbies(Request $request): JsonResponse
    {
        $user = $request->user();
        $hobbies = $user->hobbies()->get(['hobbies.id', 'hobbies.name', 'hobbies.icon'])->map(function($hobby) {
            return [
                'id' => $hobby->id,
                'name' => $hobby->name,
                'icon' => $hobby->icon ? asset($hobby->icon) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'hobbies' => $hobbies,
        ]);
    }

    /**
     * Update user's hobbies
     */
    public function updateMyHobbies(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'hobby_ids' => 'required|array',
            'hobby_ids.*' => 'exists:hobbies,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $user->hobbies()->sync($request->hobby_ids);

        // Notify other users with similar hobbies
        try {
            $fcmService = app(\App\Services\FCMService::class);
            $hobbyIds = $request->hobby_ids;
            
            // Find users with at least one shared hobby (excluding current user)
            $similarUsers = \App\Models\User::where('id', '!=', $user->id)
                ->whereHas('hobbies', function ($query) use ($hobbyIds) {
                    $query->whereIn('hobbies.id', $hobbyIds);
                })
                ->whereNotNull('fcm_token')
                ->limit(10) // Limit notifications to avoid spamming
                ->get();
                
            foreach ($similarUsers as $similarUser) {
                // Find first shared hobby name for the massage
                $sharedHobby = $similarUser->hobbies()
                    ->whereIn('hobbies.id', $hobbyIds)
                    ->first();

                $fcmService->sendToUser(
                    $similarUser->id,
                    'شخص جديد يشاركك اهتماماتك! ✨',
                    "{$user->name} يهتم بـ {$sharedHobby->name} أيضاً. تواصل معه الآن!",
                    ['type' => 'similar_hobbies', 'user_id' => $user->id]
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('FCM Similar Hobbies Error: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Hobbies updated successfully',
            'hobbies' => $user->hobbies()->get(['hobbies.id', 'hobbies.name', 'hobbies.icon'])->map(function($hobby) {
                return [
                    'id' => $hobby->id,
                    'name' => $hobby->name,
                    'icon' => $hobby->icon ? asset($hobby->icon) : null,
                ];
            }),
        ]);
    }

    /**
     * Create new hobby/hobbies (Admin only)
     * Icons can be uploaded as image files
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required_without:names|string|max:255|unique:hobbies,name',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'names' => 'required_without:name|array',
            'names.*' => 'string|max:255|distinct|unique:hobbies,name',
            'icons' => 'nullable|array',
            'icons.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

    // Single hobby creation
        if ($request->has('name')) {
            $iconPath = null;
            if ($request->hasFile('icon')) {
                $file = $request->file('icon');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('hobby_icons'), $filename);
                $iconPath = 'hobby_icons/' . $filename;
            }

            $hobby = Hobby::create([
                'name' => $request->name,
                'icon' => $iconPath,
            ]);

            // Format response
            $hobbyData = [
                'id' => $hobby->id,
                'name' => $hobby->name,
                'icon' => $hobby->icon ? asset($hobby->icon) : null,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Hobby created successfully',
                'hobby' => $hobbyData,
            ], 201);
        }

        // Multiple hobbies creation
        $createdHobbies = [];
        $icons = $request->file('icons') ?? [];
        
        foreach ($request->names as $index => $name) {
            $iconPath = null;
            if (isset($icons[$index])) {
                $file = $icons[$index];
                $filename = time() . '_' . uniqid() . '_' . $index . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('hobby_icons'), $filename);
                $iconPath = 'hobby_icons/' . $filename;
            }

            $hobby = Hobby::create([
                'name' => $name,
                'icon' => $iconPath,
            ]);

            $createdHobbies[] = [
                'id' => $hobby->id,
                'name' => $hobby->name,
                'icon' => $hobby->icon ? asset($hobby->icon) : null,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => count($createdHobbies) . ' hobbies created successfully',
            'hobbies' => $createdHobbies,
        ], 201);
    }

    /**
     * Update specific hobby (Admin only)
     * POST method to handle file uploads
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $hobby = Hobby::find($id);

        if (!$hobby) {
            return response()->json([
                'success' => false,
                'message' => 'Hobby not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255|unique:hobbies,name,' . $id,
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('name')) {
            $hobby->name = $request->name;
        }

        if ($request->hasFile('icon')) {
            // Delete old icon if exists
            if ($hobby->icon && file_exists(public_path($hobby->icon))) {
                unlink(public_path($hobby->icon));
            }

            $file = $request->file('icon');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('hobby_icons'), $filename);
            $hobby->icon = 'hobby_icons/' . $filename;
        }

        $hobby->save();

        return response()->json([
            'success' => true,
            'message' => 'Hobby updated successfully',
            'hobby' => [
                'id' => $hobby->id,
                'name' => $hobby->name,
                'icon' => $hobby->icon ? asset($hobby->icon) : null,
            ],
        ]);
    }

    /**
     * Delete hobby (Admin only)
     */
    public function destroy(int $id): JsonResponse
    {
        $hobby = Hobby::find($id);

        if (!$hobby) {
            return response()->json([
                'success' => false,
                'message' => 'Hobby not found',
            ], 404);
        }

        $hobby->delete();

        return response()->json([
            'success' => true,
            'message' => 'Hobby deleted successfully',
        ]);
    }
}
