<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
use App\Models\PostReport;
use App\Models\Block;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    protected $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Get social feed (posts sorted by latest activity)
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        // Collect IDs to exclude: users this user blocked + users who blocked this user
        $blockedIds = [];
        if ($user) {
            $blockedByMe = Block::where('blocker_id', $user->id)->pluck('blocked_id')->toArray();
            $blockedMe   = Block::where('blocked_id', $user->id)->pluck('blocker_id')->toArray();
            $blockedIds  = array_unique(array_merge($blockedByMe, $blockedMe));
        }

        // Two-tier sorting:
        // 1. Posts with likes come first
        // 2. Within each group, sort by most recent activity (like time or creation time)
        $posts = Post::query()
            ->selectRaw('posts.*, (SELECT MAX(created_at) FROM likes WHERE likes.post_id = posts.id) as last_liked_at')
            ->with(['user:id,name,nickname,profile_image', 'likes', 'comments.user:id,name,nickname,profile_image'])
            ->withCount('likes', 'comments')
            ->when(!empty($blockedIds), fn($q) => $q->whereNotIn('user_id', $blockedIds))
            ->orderByRaw('CASE WHEN (SELECT MAX(created_at) FROM likes WHERE likes.post_id = posts.id) IS NOT NULL THEN 0 ELSE 1 END')
            ->orderByRaw('COALESCE(last_liked_at, posts.created_at) DESC')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'posts' => $this->formatPosts($posts),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ]
        ]);
    }

    /**
     * Create a new post
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content'   => 'nullable|string',
            'image'     => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'latitude'  => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        // Ensure at least content or image is present
        $validator->after(function ($validator) use ($request) {
            if (!$request->content && !$request->hasFile('image')) {
                $validator->errors()->add('content', 'Post must contain text or an image.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('post_images'), $filename);
            $imagePath = 'post_images/' . $filename;
        }

        $post = $request->user()->posts()->create([
            'content'   => $request->content,
            'image'     => $imagePath,
            'latitude'  => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully',
            'post' => $this->formatPost($post->load('user')),
        ], 201);
    }

    /**
     * Show specific post
     */
    public function show($id): JsonResponse
    {
        $post = Post::with(['user', 'likes', 'comments.user'])->withCount('likes', 'comments')->find($id);

        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post not found'], 404);
        }

        return response()->json([
            'success' => true,
            'post' => $this->formatPost($post),
        ]);
    }

    /**
     * Update post (User can edit their own post)
     */
    public function update(Request $request, $id): JsonResponse
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post not found'], 404);
        }

        if ($post->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('content')) {
            $post->content = $request->content;
        }

        if ($request->hasFile('image')) {
            // Delete old image
            if ($post->image && file_exists(public_path($post->image))) {
                unlink(public_path($post->image));
            }

            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('post_images'), $filename);
            $post->image = 'post_images/' . $filename;
        }

        $post->save();

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully',
            'post' => $this->formatPost($post->load('user')),
        ]);
    }

    /**
     * Delete post
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post not found'], 404);
        }

        if ($post->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($post->image && file_exists(public_path($post->image))) {
            unlink(public_path($post->image));
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully',
        ]);
    }

    /**
     * Toggle Like
     */
    public function like(Request $request, $id): JsonResponse
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post not found'], 404);
        }

        $user = $request->user();
        $likes = $post->likes();

        if ($likes->where('user_id', $user->id)->exists()) {
            $likes->detach($user->id);
            $message = 'Post unliked';
            $liked = false;
        } else {
            $likes->attach($user->id);
            $message = 'Post liked';
            $liked = true;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'liked' => $liked,
            'likes_count' => $post->likes()->count(),
        ]);
    }

    /**
     * Add Comment
     */
    public function comment(Request $request, $id): JsonResponse
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->content,
        ]);

        // Load user for response
        $comment->load('user');

        // Send notification to post owner
        if ($post->user_id !== $request->user()->id) {
            $this->fcmService->sendToUser(
                $post->user_id,
                'تعليق جديد',
                "{$request->user()->name} علق على منشورك: " . \Illuminate\Support\Str::limit($request->content, 50),
                [
                    'type' => 'new_comment',
                    'post_id' => (string) $post->id,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'comment' => [
                'id' => $comment->id,
                'content' => $comment->content,
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'profile_image' => $comment->user->profile_image ? asset(Storage::url($comment->user->profile_image)) : null,
                ],
                'created_at' => $comment->created_at,
            ],
            'comments_count' => $post->comments()->count(),
        ], 201);
    }

    /**
     * Get current user's posts
     */
    public function myPosts(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $posts = Post::query()
            ->where('user_id', $user->id)
            ->selectRaw('posts.*, (SELECT MAX(created_at) FROM likes WHERE likes.post_id = posts.id) as last_liked_at')
            ->with(['likes', 'comments.user:id,name,nickname,profile_image'])
            ->withCount('likes', 'comments')
            ->orderByRaw('CASE WHEN (SELECT MAX(created_at) FROM likes WHERE likes.post_id = posts.id) IS NOT NULL THEN 0 ELSE 1 END')
            ->orderByRaw('COALESCE(last_liked_at, posts.created_at) DESC')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'posts' => $this->formatPosts($posts),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ]
        ]);
    }

    /**
     * Get User Profile with Posts and Hobbies
     */
    public function userProfile($userId): JsonResponse
    {
        $user = User::with(['hobbies', 'posts' => function($query) {
            $query->orderBy('updated_at', 'desc');
        }, 'posts.likes', 'posts.comments'])->find($userId);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $formattedPosts = $user->posts->map(function($post) {
            return $this->formatPost($post);
        });

        // Format hobbies
        $formattedHobbies = $user->hobbies->map(function($hobby) {
            return [
                'id' => $hobby->id,
                'name' => $hobby->name,
                'icon' => $hobby->icon ? asset($hobby->icon) : null,
            ];
        });

        $currentUser = auth('api')->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'nickname' => $user->nickname,
                'profile_image' => $user->profile_image ? asset(Storage::url($user->profile_image)) : null,
                'friendship_status' => $currentUser ? $currentUser->getFriendshipStatus($user->id) : 'not_friend',
                'hobbies' => $formattedHobbies,
                'posts' => $formattedPosts,
            ]
        ]);
    }

    /**
     * Report a post
     */
    public function report(Request $request, $id): JsonResponse
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json(['success' => false, 'message' => 'البوست غير موجود'], 404);
        }

        $user = $request->user();

        if ($post->user_id === $user->id) {
            return response()->json(['success' => false, 'message' => 'لا يمكنك الإبلاغ عن بوستك'], 400);
        }

        // Check already reported
        $alreadyReported = PostReport::where('reporter_id', $user->id)
            ->where('post_id', $id)
            ->exists();

        if ($alreadyReported) {
            return response()->json(['success' => false, 'message' => 'لقد أبلغت عن هذا البوست من قبل'], 409);
        }

        PostReport::create([
            'reporter_id' => $user->id,
            'post_id'     => $id,
            'reason'      => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال البلاغ بنجاح، سيتم مراجعته',
        ]);
    }

    /**
     * Get current user's reports
     */
    public function myReports(Request $request): JsonResponse
    {
        $user = $request->user();

        $reports = PostReport::where('reporter_id', $user->id)
            ->with(['post' => function ($q) {
                $q->with('user:id,name,nickname,profile_image');
            }])
            ->latest()
            ->get()
            ->map(function ($report) {
                $post = $report->post;
                return [
                    'report_id'  => $report->id,
                    'reason'     => $report->reason,
                    'created_at' => $report->created_at,
                    'post'       => $post ? [
                        'id'      => $post->id,
                        'content' => $post->content,
                        'image'   => $post->image ? asset($post->image) : null,
                        'user'    => $post->user ? [
                            'id'            => $post->user->id,
                            'name'          => $post->user->name,
                            'profile_image' => $post->user->profile_image
                                ? asset(\Illuminate\Support\Facades\Storage::url($post->user->profile_image))
                                : null,
                        ] : null,
                    ] : null,
                ];
            });

        return response()->json([
            'success' => true,
            'reports' => $reports,
            'total'   => $reports->count(),
        ]);
    }

    /**
     * Update (edit reason of) a report
     */
    public function updateReport(Request $request, $reportId): JsonResponse
    {
        $user   = $request->user();
        $report = PostReport::where('id', $reportId)
            ->where('reporter_id', $user->id)
            ->first();

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'البلاغ غير موجود أو لا تملك صلاحية تعديله',
            ], 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $report->update(['reason' => $request->reason]);

        return response()->json([
            'success'   => true,
            'message'   => 'تم تعديل البلاغ بنجاح',
            'report_id' => $report->id,
            'reason'    => $report->reason,
        ]);
    }

    /**
     * Delete (withdraw) a report
     */
    public function deleteReport(Request $request, $reportId): JsonResponse
    {
        $user   = $request->user();
        $report = PostReport::where('id', $reportId)
            ->where('reporter_id', $user->id)
            ->first();

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'البلاغ غير موجود أو لا تملك صلاحية حذفه',
            ], 404);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف البلاغ بنجاح',
        ]);
    }

    private function formatPost($post)
    {
        $currentUser = auth('api')->user();

        // Calculate distance between current user and the post's location
        $distanceKm = null;
        if ($currentUser && $currentUser->latitude && $currentUser->longitude 
            && $post->latitude && $post->longitude) {
            $distanceKm = $this->calculateDistance(
                $currentUser->latitude, $currentUser->longitude,
                $post->latitude, $post->longitude
            );
        }

        return [
            'id'             => $post->id,
            'content'        => $post->content,
            'image'          => $post->image ? asset($post->image) : null,
            'latitude'       => $post->latitude,
            'longitude'      => $post->longitude,
            'likes_count'    => $post->likes_count ?? $post->likes()->count(),
            'comments_count' => $post->comments_count ?? $post->comments()->count(),
            'created_at'     => $post->created_at,
            'updated_at'     => $post->updated_at,
            'distance_km'    => $distanceKm,
            'user' => [
                'id'               => $post->user->id,
                'name'             => $post->user->name,
                'nickname'         => $post->user->nickname,
                'profile_image'    => $post->user->profile_image ? asset(Storage::url($post->user->profile_image)) : null,
                'friendship_status' => !$currentUser 
                    ? 'not_friend'
                    : ($currentUser->id == $post->user->id 
                        ? 'my-self' 
                        : $currentUser->getFriendshipStatus($post->user->id)),
            ],
            'comments' => $post->comments->map(function($comment) use ($currentUser) {
                return [
                    'id'         => $comment->id,
                    'content'    => $comment->content,
                    'created_at' => $comment->created_at,
                    'user' => [
                        'id'               => $comment->user->id,
                        'name'             => $comment->user->name,
                        'profile_image'    => $comment->user->profile_image ? asset(Storage::url($comment->user->profile_image)) : null,
                        'friendship_status' => !$currentUser 
                            ? 'not_friend'
                            : ($currentUser->id == $comment->user->id 
                                ? 'my-self' 
                                : $currentUser->getFriendshipStatus($comment->user->id)),
                    ],
                ];
            }),
            'liked_by_current_user' => $currentUser ? $post->likes()->where('user_id', $currentUser->id)->exists() : false,
        ];
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     * Returns distance in kilometers, rounded to 2 decimal places
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    private function formatPosts($posts)
    {
        $formatted = [];
        foreach($posts as $post) {
            $formatted[] = $this->formatPost($post);
        }

        return $formatted;
    }
}
