<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Models\Like;

class CommentController extends Controller
{
    protected $auth;

    public function __construct()
    {
        $this->auth = app('firebase.auth');
    }

    public function index($post_id, Request $request)
    {
        // 投稿 + 投稿者情報
        $post = Post::with('user:id,name')->find($post_id);

        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        // コメント一覧
        $comments = Comment::with('user:id,name')
            ->where('post_id', $post_id)
            ->orderBy('created_at', 'asc')
            ->get();

        // いいね数
        $likesCount = Like::where('post_id', $post_id)->count();

        // =====================
        // Firebase トークン → current user 判定
        // =====================
        $currentUser = null;
        $authHeader = $request->header('Authorization', '');

        if (preg_match('/^Bearer\s+(.*)$/i', $authHeader, $matches)) {
            try {
                $verifiedIdToken = $this->auth->verifyIdToken($matches[1]);
                $firebaseUid = $verifiedIdToken->claims()->get('sub');
                $currentUser = User::where('firebase_uid', $firebaseUid)->first();
            } catch (\Throwable $e) {
                $currentUser = null;
            }
        }

        // liked / is_mine 判定
        $liked = $currentUser ? $post->isLikedBy($currentUser) : false;
        $isMine = $currentUser ? ($post->user_id === $currentUser->id) : false;

        return response()->json([
            'post' => $post,
            'comments' => $comments,
            'likes' => $likesCount,
            'liked' => $liked,
            'is_mine' => $isMine,
        ]);
    }

    public function store(Request $request, $post_id)
    {
        $request->validate([
            'content' => 'required|string|max:255',
        ]);

        // Firebase ID トークン取得
        $idToken = $request->bearerToken();

        if (!$idToken) {
            return response()->json(['error' => 'Missing ID token'], 401);
        }

        try {
            $verifiedIdToken = $this->auth->verifyIdToken($idToken);
            $firebaseUid = $verifiedIdToken->claims()->get('sub');
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Invalid ID token'], 401);
        }

        // ==========================
        // Firebase UID → local user
        // 同じ処理を PostController と合わせる！
        // ==========================
        $user = User::where('firebase_uid', $firebaseUid)->first();

        if (!$user) {
            // Firebaseから情報取得
            try {
                $firebaseUser = $this->auth->getUser($firebaseUid);
                $email = $firebaseUser->email ?? null;
                $name = $firebaseUser->displayName ?? ($email ? explode('@', $email)[0] : '');
            } catch (\Throwable $e) {
                $email = null;
                $name = '';
            }

            $user = User::create([
                'name' => $name ?? '',
                'email' => $email ?? 'user_'.$firebaseUid.'@example.com',
                'password' => bin2hex(random_bytes(16)),
                'firebase_uid' => $firebaseUid,
            ]);
        }

        // 投稿存在チェック
        $post = Post::find($post_id);
        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        // コメント作成
        $comment = Comment::create([
            'user_id' => $user->id,
            'post_id' => $post_id,
            'content' => $request->content,
        ]);

        // user を含めた形で返却
        $commentWithUser = Comment::with('user:id,name')->find($comment->id);

        return response()->json([
            'message' => 'Comment created',
            'comment' => $commentWithUser,
        ], 201);
    }
}