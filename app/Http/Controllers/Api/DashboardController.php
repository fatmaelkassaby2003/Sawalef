<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Post;
use App\Models\Hobby;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get overall dashboard statistics
     */
    public function statistics(): JsonResponse
    {
        $totalUsers = User::count();
        $totalPosts = Post::count();
        $totalHobbies = Hobby::count();
        
        // Users registered in last 30 days
        $newUsersThisMonth = User::where('created_at', '>=', now()->subDays(30))->count();
        
        // Posts created in last 30 days
        $newPostsThisMonth = Post::where('created_at', '>=', now()->subDays(30))->count();
        
        // Gender distribution
        $genderDistribution = User::select('gender', DB::raw('count(*) as count'))
            ->groupBy('gender')
            ->get()
            ->mapWithKeys(fn($item) => [$item->gender => $item->count]);
        
        // Top countries
        $topCountries = User::select('country', DB::raw('count(*) as count'))
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit(5)
            ->get();
        
        // Average hobbies per user
        $avgHobbiesPerUser = DB::table('user_hobbies')
            ->select(DB::raw('count(*) / count(DISTINCT user_id) as avg'))
            ->value('avg');
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'total_posts' => $totalPosts,
                'total_hobbies' => $totalHobbies,
                'new_users_this_month' => $newUsersThisMonth,
                'new_posts_this_month' => $newPostsThisMonth,
                'gender_distribution' => $genderDistribution,
                'top_countries' => $topCountries,
                'avg_hobbies_per_user' => round($avgHobbiesPerUser ?? 0, 2),
            ]
        ]);
    }

    /**
     * Get users analytics with filters
     */
    public function users(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        $query = User::query();
        
        // Apply filters
        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }
        
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('nickname', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }
        
        $users = $query->with('hobbies:id,name,icon')
            ->withCount('posts')
            ->withCount('hobbies')
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);
        
        // User growth trend (last 7 days)
        $userGrowth = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'nickname' => $user->nickname,
                        'phone' => $user->phone,
                        'age' => $user->age,
                        'country' => $user->country_name ?? $user->country,
                        'gender' => $user->gender,
                        'profile_image' => $user->profile_image ? url('storage/' . $user->profile_image) : null,
                        'posts_count' => $user->posts_count,
                        'hobbies_count' => $user->hobbies_count,
                        'hobbies' => $user->hobbies->map(fn($h) => [
                            'id' => $h->id,
                            'name' => $h->name,
                            'icon' => $h->icon ? url($h->icon) : null,
                        ]),
                        'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'pagination' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                ],
                'user_growth' => $userGrowth,
            ]
        ]);
    }

    /**
     * Get posts analytics
     */
    public function posts(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        
        $query = Post::query();
        
        if ($request->filled('search')) {
            $query->where('content', 'like', '%' . $request->search . '%');
        }
        
        $posts = $query->with(['user:id,name,nickname,profile_image'])
            ->withCount('comments')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
        
        // Posts activity (last 7 days)
        $postsActivity = Post::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'posts' => $posts->map(function($post) {
                    return [
                        'id' => $post->id,
                        'content' => $post->content,
                        'image' => $post->image ? asset('storage/' . $post->image) : null,
                        'comments_count' => $post->comments_count,
                        'user' => [
                            'id' => $post->user->id,
                            'name' => $post->user->name,
                            'nickname' => $post->user->nickname,
                            'profile_image' => $post->user->profile_image ? asset('storage/' . $post->user->profile_image) : null,
                        ],
                        'created_at' => $post->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'pagination' => [
                    'total' => $posts->total(),
                    'per_page' => $posts->perPage(),
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                ],
                'posts_activity' => $postsActivity,
            ]
        ]);
    }

    /**
     * Get hobbies statistics
     */
    public function hobbies(): JsonResponse
    {
        $hobbies = Hobby::withCount('users')
            ->orderBy('users_count', 'desc')
            ->get()
            ->map(function($hobby) {
                return [
                    'id' => $hobby->id,
                    'name' => $hobby->name,
                    'icon' => $hobby->icon ? asset($hobby->icon) : null,
                    'users_count' => $hobby->users_count,
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => [
                'hobbies' => $hobbies,
                'total_hobbies' => $hobbies->count(),
            ]
        ]);
    }

    /**
     * Get matching activity analytics
     */
    public function matches(): JsonResponse
    {
        // Get users with most hobbies (most matchable)
        $mostMatchableUsers = User::withCount('hobbies')
            ->orderBy('hobbies_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nickname' => $user->nickname,
                    'hobbies_count' => $user->hobbies_count,
                    'profile_image' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                ];
            });
        
        // Country distribution
        $countryStats = User::select('country', DB::raw('count(*) as count'))
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderByDesc('count')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'most_matchable_users' => $mostMatchableUsers,
                'country_distribution' => $countryStats,
            ]
        ]);
    }
}
