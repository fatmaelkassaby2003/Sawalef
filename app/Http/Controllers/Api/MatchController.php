<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MatchController extends Controller
{
    /**
     * Get users with 80%+ hobby match
     */
    public function getSimilarUsers(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        
        // Get current user's hobby IDs
        $userHobbyIds = $currentUser->hobbies()->pluck('hobbies.id')->toArray();
        
        if (empty($userHobbyIds)) {
            return response()->json([
                'success' => true,
                'message' => 'You need to add hobbies to find similar users',
                'similar_users' => [],
            ]);
        }

        $userHobbyCount = count($userHobbyIds);
        $minMatchCount = ceil($userHobbyCount * 0.8); // 80% minimum

        // Find users with matching hobbies
        // Find users with matching hobbies
        $similarUserIds = DB::table('user_hobbies')
            ->whereIn('hobby_id', $userHobbyIds)
            ->where('user_id', '!=', $currentUser->id)
            ->groupBy('user_id')
            ->havingRaw('COUNT(DISTINCT hobby_id) >= ?', [$minMatchCount])
            ->pluck('user_id');

        $similarUsers = User::whereIn('id', $similarUserIds)
            ->with(['hobbies' => function($query) {
                $query->select('hobbies.id', 'hobbies.name', 'hobbies.icon');
            }])
            ->get();

        // Calculate match percentage for each user
        $formattedUsers = $similarUsers->map(function ($user) use ($userHobbyIds) {
            $matchingHobbies = $user->hobbies->pluck('id')->toArray();
            $commonHobbies = array_intersect($userHobbyIds, $matchingHobbies);
            $matchPercentage = (count($commonHobbies) / count($userHobbyIds)) * 100;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'nickname' => $user->nickname,
                'age' => $user->age,
                'country' => $user->country,
                'gender' => $user->gender,
                'profile_image' => $user->profile_image ? asset(Storage::url($user->profile_image)) : null,
                'hobbies' => $user->hobbies->map(fn($h) => [
                    'id' => $h->id, 
                    'name' => $h->name,
                    'icon' => $h->icon ? asset($h->icon) : null
                ]),
                'match_percentage' => round($matchPercentage, 2),
                'common_hobbies_count' => count($commonHobbies),
            ];
        });

        // Sort by match percentage (highest first)
        $formattedUsers = $formattedUsers->sortByDesc('match_percentage')->values();

        return response()->json([
            'success' => true,
            'your_hobbies_count' => count($userHobbyIds),
            'similar_users' => $formattedUsers,
        ]);
    }
}
