<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
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
        // Two-tier sorting:
        // 1. Posts with likes come first
        // 2. Within each group, sort by most recent activity (like time or creation time)
        $posts = Post::query()
            ->selectRaw('posts.*, (SELECT MAX(created_at) FROM likes WHERE likes.post_id = posts.id) as last_liked_at')
            ->with(['user:id,name,nickname,profile_image', 'likes', 'comments.user:id,name,nickname,profile_image'])
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
     * Create a new post
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
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
            'content' => $request->content,
            'image' => $imagePath,
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

    private function formatPost($post)
    {
        $currentUser = auth('api')->user();
        
        \Log::info('formatPost called', [
            'post_id' => $post->id,
            'post_user_id' => $post->user->id,
            'current_user' => $currentUser ? $currentUser->id : 'null'
        ]);
        
        return [
            'id' => $post->id,
            'content' => $post->content,
            'image' => $post->image ? asset($post->image) : null,
            'likes_count' => $post->likes_count ?? $post->likes()->count(),
            'comments_count' => $post->comments_count ?? $post->comments()->count(),
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
            'user' => [
                'id' => $post->user->id,
                'name' => $post->user->name,
                'nickname' => $post->user->nickname,
                'profile_image' => $post->user->profile_image ? asset(Storage::url($post->user->profile_image)) : null,
                'friendship_status' => ($currentUser && $currentUser->id != $post->user->id) 
                    ? $currentUser->getFriendshipStatus($post->user->id) 
                    : 'not_friend',
            ],
            'comments' => $post->comments->map(function($comment) use ($currentUser) {
                return [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'created_at' => $comment->created_at,
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                        'profile_image' => $comment->user->profile_image ? asset(Storage::url($comment->user->profile_image)) : null,
                        'friendship_status' => ($currentUser && $currentUser->id != $comment->user->id) 
                            ? $currentUser->getFriendshipStatus($comment->user->id) 
                            : 'not_friend',
                    ],
                ];
            }),
            'liked_by_current_user' => $currentUser ? $post->likes()->where('user_id', $currentUser->id)->exists() : false,
        ];
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
