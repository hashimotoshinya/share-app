<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;

class LikeController extends Controller
{
    protected $auth;

    public function __construct()
    {
        $this->auth = app('firebase.auth');
    }

    public function toggle(Request $request, $postId)
    {
        // =====================
        // 1. Firebase ID トークン取得
        // =====================
        $idToken = $request->bearerToken();

        if (!$idToken) {
            return response()->json(['error' => 'Missing Authorization Bearer token'], 401);
        }

        try {
            $verifiedIdToken = $this->auth->verifyIdToken($idToken);
            $firebaseUid = $verifiedIdToken->claims()->get('sub');
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Invalid ID token'], 401);
        }

        // =====================
        // 2. Firebase UID → ローカルユーザー（未登録なら自動作成）
        // =====================
        $user = User::where('firebase_uid', $firebaseUid)->first();

        if (!$user) {
            // Firebaseユーザー情報取得
            try {
                $firebaseUser = $this->auth->getUser($firebaseUid);
                $email = $firebaseUser->email ?? null;
                $name  = $firebaseUser->displayName ?? ($email ? explode('@', $email)[0] : '');
            } catch (\Throwable $e) {
                $email = null;
                $name  = '';
            }

            $user = User::create([
                'name'         => $name ?? '',
                'email'        => $email ?? 'user_'.$firebaseUid.'@example.com',
                'password'     => bin2hex(random_bytes(16)),
                'firebase_uid' => $firebaseUid,
            ]);
        }

        // =====================
        // 3. 投稿取得
        // =====================
        $post = Post::find($postId);
        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        // 自分の投稿にはいいね禁止
        if ($post->user_id === $user->id) {
            return response()->json(['error' => "Cannot like your own post"], 403);
        }

        // =====================
        // 4. いいねの ON/OFF 切り替え
        // =====================
        $like = Like::where('user_id', $user->id)
                    ->where('post_id', $postId)
                    ->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            Like::create([
                'user_id' => $user->id,
                'post_id' => $postId,
            ]);
            $liked = true;
        }

        // 最新いいね数
        $count = Like::where('post_id', $postId)->count();

        return response()->json([
            'liked' => $liked,
            'count' => $count,
        ]);
    }
}