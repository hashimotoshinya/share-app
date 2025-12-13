<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証ユーザーが投稿を作成できる
     */
    public function test_authenticated_user_can_create_post()
    {
        // ユーザーを作成
        $user = User::create([
            'name' => 'Test User 1',
            'email' => 'test1@example.com',
            'firebase_uid' => 'test-uid-001',
        ]);

        $response = $this->postJson('/api/posts', [
            'content' => 'This is a test post',
        ], [
            'Authorization' => 'Bearer valid-token',
            'X-Test-Firebase-UID' => 'test-uid-001',
            'X-Test-Email' => 'test1@example.com',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'content', 'user', 'likes_count', 'liked', 'is_mine', 'created_at']);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'This is a test post',
        ]);
    }

    /**
     * 投稿一覧を取得できる
     */
    public function test_user_can_fetch_posts()
    {
        // ユーザーを作成
        $user = User::create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'firebase_uid' => 'test-uid-002',
        ]);

        Post::create([
            'user_id' => $user->id,
            'content' => 'Post 1',
        ]);

        Post::create([
            'user_id' => $user->id,
            'content' => 'Post 2',
        ]);

        $response = $this->getJson('/api/posts', [
            'Authorization' => 'Bearer valid-token',
            'X-Test-Firebase-UID' => 'test-uid-002',
            'X-Test-Email' => 'test2@example.com',
        ]);

        $response->assertStatus(200);

        $posts = $response->json();
        $this->assertIsArray($posts);
        $this->assertGreaterThanOrEqual(2, count($posts));
    }

    /**
     * 投稿を削除できる（作成者のみ）
     */
    public function test_post_owner_can_delete_post()
    {
        // ユーザーを作成
        $user = User::create([
            'name' => 'Test User 3',
            'email' => 'test3@example.com',
            'firebase_uid' => 'test-uid-003',
        ]);

        // 投稿を作成
        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Test post to delete',
        ]);

        $response = $this->deleteJson("/api/posts/{$post->id}", [], [
            'Authorization' => 'Bearer valid-token',
            'X-Test-Firebase-UID' => 'test-uid-003',
            'X-Test-Email' => 'test3@example.com',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
        ]);
    }

    /**
     * レスポンスに sensitive フィールドが含まれていない
     */
    public function test_post_response_excludes_sensitive_fields()
    {
        // ユーザーを作成
        $user = User::create([
            'name' => 'Test User 4',
            'email' => 'test4@example.com',
            'firebase_uid' => 'test-uid-004',
        ]);

        Post::create([
            'user_id' => $user->id,
            'content' => 'Test post',
        ]);

        $response = $this->getJson('/api/posts', [
            'Authorization' => 'Bearer valid-token',
            'X-Test-Firebase-UID' => 'test-uid-004',
            'X-Test-Email' => 'test4@example.com',
        ]);

        $response->assertStatus(200);

        $jsonResponse = $response->json();
        foreach ($jsonResponse as $post) {
            if (isset($post['user'])) {
                $this->assertArrayNotHasKey('password', $post['user']);
                $this->assertArrayNotHasKey('remember_token', $post['user']);
            }
        }
    }
}
