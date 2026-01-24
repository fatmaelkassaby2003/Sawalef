<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MatchController extends Controller
{
    /**
     * Get similar users based on hobbies
     */
    public function getSimilarUsers(Request $request): JsonResponse
    {
        $user = $request->user();
        $userHobbyIds = $user->hobbies()->pluck('hobbies.id')->toArray();
        
        if (empty($userHobbyIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Please add hobbies to your profile first to find similar users',
            ], 400);
        }

        $similarUsers = User::where('id', '!=', $user->id)
            ->whereHas('hobbies', function($query) use ($userHobbyIds) {
                $query->whereIn('hobbies.id', $userHobbyIds);
            })
            ->withCount([
                'hobbies as matching_hobbies_count' => function($query) use ($userHobbyIds) {
                    $query->whereIn('hobbies.id', $userHobbyIds);
                }
            ])
            ->with('hobbies:id,name,icon')
            ->get()
            ->map(function($otherUser) use ($userHobbyIds) {
                $matchPercentage = 0;
                if (count($userHobbyIds) > 0) {
                    $matchPercentage = ($otherUser->matching_hobbies_count / count($userHobbyIds)) * 100;
                }
                
                return [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'nickname' => $otherUser->nickname,
                    'age' => $otherUser->age,
                    'country_ar' => $otherUser->country_ar,
                    'country_en' => $otherUser->country_en,
                    'gender' => $otherUser->gender,
                    'profile_image' => $otherUser->profile_image ? asset('storage/' . $otherUser->profile_image) : null,
                    'match_percentage' => round($matchPercentage, 2),
                    'matching_hobbies_count' => $otherUser->matching_hobbies_count,
                    'total_user_hobbies' => count($userHobbyIds),
                    'hobbies' => $otherUser->hobbies->map(function($hobby) {
                        return [
                            'id' => $hobby->id,
                            'name' => $hobby->name,
                            'icon' => $hobby->icon ? asset($hobby->icon) : null,
                        ];
                    }),
                ];
            })
            ->sortByDesc('match_percentage')
            ->values();

        return response()->json([
            'success' => true,
            'similar_users' => $similarUsers,
        ]);
    }

    /**
     * Search for random user by country with configurable hobby match percentage
     */
    public function searchByCountry(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'country' => 'required|string|max:255',
            'exclude_user_ids' => 'nullable|array',
            'exclude_user_ids.*' => 'integer|exists:users,id',
            'min_match_percentage' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $currentUser = $request->user();
        $searchCountry = $request->country;
        $excludeUserIds = $request->input('exclude_user_ids', []);
        $minMatchPercentage = $request->input('min_match_percentage', 50); // Default to 50% instead of 80%
        
        // Get current user's hobbies
        $userHobbyIds = $currentUser->hobbies()->pluck('hobbies.id')->toArray();
        
        if (empty($userHobbyIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Please add hobbies to your profile first to search for users',
            ], 400);
        }

        $totalUserHobbies = count($userHobbyIds);
        $requiredMatches = ceil($totalUserHobbies * ($minMatchPercentage / 100));

        // Find users from the specified country with at least 80% hobby match
        $query = User::where('id', '!=', $currentUser->id)
            ->where(function($q) use ($searchCountry) {
                $q->where('country_ar', $searchCountry)
                  ->orWhere('country_en', $searchCountry);
            });
        
        // Exclude previously shown users
        if (!empty($excludeUserIds)) {
            $query->whereNotIn('id', $excludeUserIds);
        }
        
        $matchingUsers = $query
            ->whereHas('hobbies', function($query) use ($userHobbyIds, $requiredMatches) {
                $query->whereIn('hobbies.id', $userHobbyIds);
            }, '>=', $requiredMatches)
            ->withCount([
                'hobbies as matching_hobbies_count' => function($query) use ($userHobbyIds) {
                    $query->whereIn('hobbies.id', $userHobbyIds);
                }
            ])
            ->with('hobbies:id,name,icon')
            ->get()
            ->filter(function($user) use ($totalUserHobbies, $requiredMatches) {
                // Double-check the match percentage
                return $user->matching_hobbies_count >= $requiredMatches;
            });

        if ($matchingUsers->isEmpty()) {
            $message = !empty($excludeUserIds) && count($excludeUserIds) > 0
                ? "No more users found in {$searchCountry} with at least {$minMatchPercentage}% hobby match"
                : "No users found in {$searchCountry} with at least {$minMatchPercentage}% hobby match";
                
            return response()->json([
                'success' => false,
                'message' => $message,
                'required_matches' => $requiredMatches,
                'your_total_hobbies' => $totalUserHobbies,
                'excluded_count' => count($excludeUserIds),
                'min_match_percentage' => $minMatchPercentage,
            ], 404);
        }

        // Select a random user from the matching users
        $randomUser = $matchingUsers->random();
        
        $matchPercentage = ($randomUser->matching_hobbies_count / $totalUserHobbies) * 100;

        return response()->json([
            'success' => true,
            'message' => 'Random user found successfully',
            'user' => [
                'id' => $randomUser->id,
                'name' => $randomUser->name,
                'nickname' => $randomUser->nickname,
                'age' => $randomUser->age,
                'country_ar' => $randomUser->country_ar,
                'country_en' => $randomUser->country_en,
                'gender' => $randomUser->gender,
                'profile_image' => $randomUser->profile_image ? asset('storage/' . $randomUser->profile_image) : null,
                'phone' => $randomUser->phone,
                'match_percentage' => round($matchPercentage, 2),
                'matching_hobbies_count' => $randomUser->matching_hobbies_count,
                'your_total_hobbies' => $totalUserHobbies,
                'required_matches_for_threshold' => $requiredMatches,
                'min_match_percentage_used' => $minMatchPercentage,
                'hobbies' => $randomUser->hobbies->map(function($hobby) {
                    return [
                        'id' => $hobby->id,
                        'name' => $hobby->name,
                        'icon' => $hobby->icon ? asset($hobby->icon) : null,
                    ];
                }),
            ],
            'total_matching_users_in_country' => $matchingUsers->count(),
            'remaining_users' => $matchingUsers->count() - 1,
        ]);
    }

    /**
     * Advanced search with filters
     */
    public function advancedSearch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'country' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female',
            'min_age' => 'nullable|integer|min:1',
            'max_age' => 'nullable|integer|max:150',
            'min_match_percentage' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $currentUser = $request->user();
        $userHobbyIds = $currentUser->hobbies()->pluck('hobbies.id')->toArray();
        
        if (empty($userHobbyIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Please add hobbies to your profile first',
            ], 400);
        }

        $totalUserHobbies = count($userHobbyIds);
        $minMatchPercentage = $request->input('min_match_percentage', 50);
        $requiredMatches = ceil($totalUserHobbies * ($minMatchPercentage / 100));

        // Build query
        $query = User::where('id', '!=', $currentUser->id);

        // Apply filters
        if ($request->filled('country')) {
            $query->where(function($q) use ($request) {
                $q->where('country_ar', $request->country)
                  ->orWhere('country_en', $request->country);
            });
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('min_age')) {
            $query->where('age', '>=', $request->min_age);
        }

        if ($request->filled('max_age')) {
            $query->where('age', '<=', $request->max_age);
        }

        // Apply hobby matching
        $matchingUsers = $query
            ->whereHas('hobbies', function($q) use ($userHobbyIds) {
                $q->whereIn('hobbies.id', $userHobbyIds);
            })
            ->withCount([
                'hobbies as matching_hobbies_count' => function($q) use ($userHobbyIds) {
                    $q->whereIn('hobbies.id', $userHobbyIds);
                }
            ])
            ->with('hobbies:id,name,icon')
            ->get()
            ->filter(function($user) use ($requiredMatches) {
                return $user->matching_hobbies_count >= $requiredMatches;
            })
            ->map(function($user) use ($totalUserHobbies) {
                $matchPercentage = ($user->matching_hobbies_count / $totalUserHobbies) * 100;
                
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nickname' => $user->nickname,
                    'age' => $user->age,
                    'country_ar' => $user->country_ar,
                    'country_en' => $user->country_en,
                    'gender' => $user->gender,
                    'profile_image' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                    'match_percentage' => round($matchPercentage, 2),
                    'matching_hobbies_count' => $user->matching_hobbies_count,
                    'hobbies' => $user->hobbies->map(function($hobby) {
                        return [
                            'id' => $hobby->id,
                            'name' => $hobby->name,
                            'icon' => $hobby->icon ? asset($hobby->icon) : null,
                        ];
                    }),
                ];
            })
            ->sortByDesc('match_percentage')
            ->values();

        return response()->json([
            'success' => true,
            'users' => $matchingUsers,
            'total_found' => $matchingUsers->count(),
            'filters_applied' => [
                'country' => $request->country,
                'gender' => $request->gender,
                'min_age' => $request->min_age,
                'max_age' => $request->max_age,
                'min_match_percentage' => $minMatchPercentage,
            ],
        ]);
    }
}