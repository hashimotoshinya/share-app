<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentLikeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * コメントを作成できる
     */
    public function test_user_can_create_comment()
    {
        $user = User::create([
            'name' => 'Test User 1',
            'email' => 'user1@example.com',
            'firebase_uid' => 'uid-1',
        ]);

        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Test post',
        ]);

        $response = $this->postJson("/api/posts/{$post->id}/comments", [
            'content' => 'This is a comment',
        ], [
            'Authorization' => 'Bearer token',
            'X-Test-Firebase-UID' => 'uid-1',
            'X-Test-Email' => 'user1@example.com',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'This is a comment',
        ]);
    }

    /**
     * コメント一覧を取得できる
     */
    public function test_user_can_fetch_comments()
    {
        $user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'firebase_uid' => 'uid-1',
        ]);

        $user2 = User::create([
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'firebase_uid' => 'uid-2',
        ]);

        $post = Post::create([
            'user_id' => $user1->id,
            'content' => 'Test post',
        ]);

        Comment::create([
            'post_id' => $post->id,
            'user_id' => $user1->id,
            'content' => 'Comment 1',
        ]);

        Comment::create([
            'post_id' => $post->id,
            'user_id' => $user2->id,
            'content' => 'Comment 2',
        ]);

        $response = $this->getJson("/api/posts/{$post->id}/comments", [
            'Authorization' => 'Bearer token',
            'X-Test-Firebase-UID' => 'uid-1',
            'X-Test-Email' => 'user1@example.com',
        ]);

        $response->assertStatus(200);
        $this->assertIsArray($response->json());
    }

    /**
     * 投稿をいいねできる
     */
    public function test_user_can_like_post()
    {
        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'firebase_uid' => 'uid-1',
        ]);

        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Test post',
        ]);

        $response = $this->postJson("/api/posts/{$post->id}/like", [], [
            'Authorization' => 'Bearer token',
            'X-Test-Firebase-UID' => 'uid-1',
            'X-Test-Email' => 'user@example.com',
        ]);

        $response->assertStatus(403); // 自分の投稿にはいいね禁止
    }

    /**
     * 他のユーザーの投稿をいいねできる
     */
    public function test_user_can_like_other_post()
    {
        $user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'firebase_uid' => 'uid-1',
        ]);

        $user2 = User::create([
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'firebase_uid' => 'uid-2',
        ]);

        $post = Post::create([
            'user_id' => $user1->id,
            'content' => 'Test post',
        ]);

        $response = $this->postJson("/api/posts/{$post->id}/like", [], [
            'Authorization' => 'Bearer token',
            'X-Test-Firebase-UID' => 'uid-2',
            'X-Test-Email' => 'user2@example.com',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('likes', [
            'post_id' => $post->id,
            'user_id' => $user2->id,
        ]);
    }

    /**
     * いいねを取り消せる（トグル）
     */
    public function test_user_can_unlike_post()
    {
        $user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'firebase_uid' => 'uid-1',
        ]);

        $user2 = User::create([
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'firebase_uid' => 'uid-2',
        ]);

        $post = Post::create([
            'user_id' => $user1->id,
            'content' => 'Test post',
        ]);

        // いいねを作成
        Like::create([
            'post_id' => $post->id,
            'user_id' => $user2->id,
        ]);

        // いいねを削除
        $response = $this->postJson("/api/posts/{$post->id}/like", [], [
            'Authorization' => 'Bearer token',
            'X-Test-Firebase-UID' => 'uid-2',
            'X-Test-Email' => 'user2@example.com',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('likes', [
            'post_id' => $post->id,
            'user_id' => $user2->id,
        ]);
    }
}
