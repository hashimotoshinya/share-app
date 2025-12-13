<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;

class LikeController extends Controller
{

    public function toggle(Request $request, $postId)
    {
        // =====================
        // ミドルウェアから firebase_user を取得
        // =====================
        $firebaseUser = $request->attributes->get('firebase_user');
        if (!$firebaseUser || !$firebaseUser->uid) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $firebaseUid = $firebaseUser->uid;
        $email = $firebaseUser->email ?? 'user_'.$firebaseUid.'@example.com';

        // =====================
        // Firebase UID → ローカルユーザー（未登録なら自動作成）
        // =====================
        $user = User::where('firebase_uid', $firebaseUid)->first();

        if (!$user) {
            $user = User::create([
                'firebase_uid' => $firebaseUid,
                'name'         => explode('@', $email)[0],
                'email'        => $email,
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

        $statusCode = 200;
        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            Like::create([
                'user_id' => $user->id,
                'post_id' => $postId,
            ]);
            $liked = true;
            $statusCode = 201; // 作成時は 201
        }

        // 最新いいね数
        $count = Like::where('post_id', $postId)->count();

        return response()->json([
            'liked' => $liked,
            'count' => $count,
        ], $statusCode);
    }
}