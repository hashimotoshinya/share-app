<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ModelRelationshipsTest extends \Tests\TestCase
{
    use RefreshDatabase;

    /**
     * User モデルが複数の投稿を持つ
     */
    public function test_user_has_many_posts()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'firebase_uid' => 'uid-123',
        ]);

        Post::create([
            'user_id' => $user->id,
            'content' => 'Post 1',
        ]);

        Post::create([
            'user_id' => $user->id,
            'content' => 'Post 2',
        ]);

        $this->assertCount(2, $user->posts);
    }

    /**
     * User モデルが複数のコメントを持つ
     */
    public function test_user_has_many_comments()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'firebase_uid' => 'uid-123',
        ]);

        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Post',
        ]);

        Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'Comment 1',
        ]);

        Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'Comment 2',
        ]);

        $this->assertCount(2, $user->comments);
    }

    /**
     * User モデルが複数のいいねを持つ
     */
    public function test_user_has_many_likes()
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

        $post1 = Post::create([
            'user_id' => $user1->id,
            'content' => 'Post 1',
        ]);

        $post2 = Post::create([
            'user_id' => $user1->id,
            'content' => 'Post 2',
        ]);

        Like::create([
            'post_id' => $post1->id,
            'user_id' => $user2->id,
        ]);

        Like::create([
            'post_id' => $post2->id,
            'user_id' => $user2->id,
        ]);

        $this->assertCount(2, $user2->likes);
    }

    /**
     * Post モデルがユーザーを持つ
     */
    public function test_post_belongs_to_user()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'firebase_uid' => 'uid-123',
        ]);

        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Test post',
        ]);

        $this->assertInstanceOf(User::class, $post->user);
        $this->assertEquals($user->id, $post->user->id);
    }

    /**
     * Post モデルが複数のコメントを持つ
     */
    public function test_post_has_many_comments()
    {
        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'firebase_uid' => 'uid-123',
        ]);

        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Post',
        ]);

        Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'Comment 1',
        ]);

        Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'Comment 2',
        ]);

        $this->assertCount(2, $post->comments);
    }

    /**
     * Post モデルが複数のいいねを持つ
     */
    public function test_post_has_many_likes()
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
            'content' => 'Post',
        ]);

        Like::create([
            'post_id' => $post->id,
            'user_id' => $user1->id,
        ]);

        Like::create([
            'post_id' => $post->id,
            'user_id' => $user2->id,
        ]);

        $this->assertCount(2, $post->likes);
    }

    /**
     * Comment モデルがユーザーを持つ
     */
    public function test_comment_belongs_to_user()
    {
        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'firebase_uid' => 'uid-123',
        ]);

        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Post',
        ]);

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'Comment',
        ]);

        $this->assertInstanceOf(User::class, $comment->user);
        $this->assertEquals($user->id, $comment->user->id);
    }

    /**
     * Comment モデルが投稿を持つ
     */
    public function test_comment_belongs_to_post()
    {
        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'firebase_uid' => 'uid-123',
        ]);

        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Post',
        ]);

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'Comment',
        ]);

        $this->assertInstanceOf(Post::class, $comment->post);
        $this->assertEquals($post->id, $comment->post->id);
    }

    /**
     * Like モデルがユーザーを持つ
     */
    public function test_like_belongs_to_user()
    {
        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'firebase_uid' => 'uid-123',
        ]);

        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Post',
        ]);

        $like = Like::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $like->user);
        $this->assertEquals($user->id, $like->user->id);
    }

    /**
     * Like モデルが投稿を持つ
     */
    public function test_like_belongs_to_post()
    {
        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'firebase_uid' => 'uid-123',
        ]);

        $post = Post::create([
            'user_id' => $user->id,
            'content' => 'Post',
        ]);

        $like = Like::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Post::class, $like->post);
        $this->assertEquals($post->id, $like->post->id);
    }
}
