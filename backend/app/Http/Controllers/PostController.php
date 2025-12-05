<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\User;

class PostController extends Controller
{
    /**
     * Firebase UID からローカルユーザーを取得 or 作成
     */
    private function getLocalUser(Request $request)
    {
        $firebaseUser = $request->attributes->get('firebase_user');

        if (!$firebaseUser || !$firebaseUser->uid) {
            return null;
        }

        $firebaseUid = $firebaseUser->uid;
        $email = $firebaseUser->email ?? ('user_'.$firebaseUid.'@example.com');

        // すでに存在するか確認
        $localUser = User::where('firebase_uid', $firebaseUid)->first();

        // なければ作成
        if (!$localUser) {
            $localUser = User::create([
                'name'         => explode('@', $email)[0],
                'email'        => $email,
                'firebase_uid' => $firebaseUid,
                'password'     => '',
            ]);
        }

        return $localUser;
    }

    /**
     * 投稿一覧
     */
    public function index(Request $request)
    {
        $localUser = $this->getLocalUser($request);

        $posts = Post::with('user:id,name')
            ->withCount('likes')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $posts->through(function ($post) use ($localUser) {
            return [
                'id'          => $post->id,
                'content'     => $post->content,
                'user'        => $post->user,
                'likes_count' => $post->likes_count,
                'liked'       => $localUser ? $post->isLikedBy($localUser) : false,
                'is_mine'     => $localUser ? $post->user_id === $localUser->id : false,
                'created_at'  => $post->created_at->diffForHumans(),
            ];
        });
    }

    /**
     * 投稿作成
     */
    public function store(Request $request)
    {
        $localUser = $this->getLocalUser($request);

        if (!$localUser) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'content' => 'required|string|max:10000',
        ]);

        $post = Post::create([
            'user_id' => $localUser->id,
            'content' => $data['content'],
        ]);

        $post->load('user:id,name');

        return response()->json([
            'id'          => $post->id,
            'content'     => $post->content,
            'user'        => $post->user,
            'likes_count' => 0,
            'liked'       => false,
            'is_mine'     => true,
            'created_at'  => $post->created_at->diffForHumans(),
        ], 201);
    }

    /**
     * 投稿削除
     */
    public function destroy(Request $request, $id)
    {
        $localUser = $this->getLocalUser($request);

        if (!$localUser) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $post = Post::find($id);

        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        if ($post->user_id !== $localUser->id) {
            return response()->json(['error' => 'Not allowed: only owner can delete this post'], 403);
        }

        $post->delete();

        return response()->json(['message' => 'Deleted successfully'], 200);
    }
}