<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Models\Like;

class CommentController extends Controller
{

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
        // ミドルウェアから firebase_user を取得
        // =====================
        $currentUser = null;
        $firebaseUser = $request->attributes->get('firebase_user');
        if ($firebaseUser && $firebaseUser->uid) {
            $currentUser = User::where('firebase_uid', $firebaseUser->uid)->first();
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
            'content' => 'required|string|max:120',
        ]);

        // ★ ミドルウェアから firebase_user を取得（テスト環境でもサポート）
        $firebaseUser = $request->attributes->get('firebase_user');
        if (!$firebaseUser || !$firebaseUser->uid) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $firebaseUid = $firebaseUser->uid;
        $email = $firebaseUser->email ?? 'user_'.$firebaseUid.'@example.com';

        // ==========================
        // Firebase UID → local user
        // ==========================
        $user = User::where('firebase_uid', $firebaseUid)->first();

        if (!$user) {
            $user = User::create([
                'firebase_uid' => $firebaseUid,
                'name' => explode('@', $email)[0],
                'email' => $email,
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