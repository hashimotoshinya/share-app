<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class FirebaseAuthMappingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * モック Firebase 認証レスポンスを注入するヘルパー
     */
    private function mockFirebaseVerification(string $uid, string $email)
    {
        // モック Claims オブジェクト
        $mockClaims = Mockery::mock();
        $mockClaims->shouldReceive('get')
                   ->with('sub')
                   ->andReturn($uid);
        $mockClaims->shouldReceive('get')
                   ->with('email')
                   ->andReturn($email);
        $mockClaims->shouldReceive('get')
                   ->andReturn(null); // その他のキーは null

        // モック Verified オブジェクト（claims() メソッドを持つ）
        $mockVerified = Mockery::mock();
        $mockVerified->shouldReceive('claims')
                     ->andReturn($mockClaims);

        // Firebase Auth サービスをモック
        $mockAuth = Mockery::mock();
        $mockAuth->shouldReceive('verifyIdToken')
                 ->andReturn($mockVerified);

        app()->instance('firebase.auth', $mockAuth);
    }

    /**
     * 新しい Firebase UID で新規ユーザーを作成できる
     */
    public function test_register_with_new_firebase_uid_creates_user()
    {
        $firebaseUid = 'new-firebase-uid-123';
        $email = 'new@example.com';
        $username = 'newuser';

        $response = $this->postJson('/api/register/firebase', [
            'username' => $username,
            'email' => $email,
        ], [
            'X-Test-Firebase-UID' => $firebaseUid,
            'X-Test-Email' => $email,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'User registered/verified']);

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'firebase_uid' => $firebaseUid,
            'name' => $username,
        ]);
    }

    public function test_register_with_existing_email_and_firebase_uid_updates_name()
    {
        $firebaseUid = 'existing-firebase-uid-123';
        $email = 'existing@example.com';

        $existingUser = User::create([
            'name' => 'oldname',
            'email' => $email,
            'firebase_uid' => $firebaseUid,
        ]);

        $response = $this->postJson('/api/register/firebase', [
            'username' => 'updated_username',
            'email' => $email,
        ], [
            'X-Test-Firebase-UID' => $firebaseUid,
            'X-Test-Email' => $email,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $existingUser->id,
            'firebase_uid' => $firebaseUid,
            'name' => 'updated_username',
        ]);
    }

    public function test_register_with_existing_firebase_uid_updates_name()
    {
        $firebaseUid = 'existing-firebase-uid';
        $email = 'existing@example.com';

        $existingUser = User::create([
            'name' => 'originalname',
            'email' => $email,
            'firebase_uid' => $firebaseUid,
        ]);

        $response = $this->postJson('/api/register/firebase', [
            'username' => 'newusername',
            'email' => $email,
        ], [
            'X-Test-Firebase-UID' => $firebaseUid,
            'X-Test-Email' => $email,
        ]);

        $response->assertStatus(200);

        $updatedUser = User::find($existingUser->id);
        $this->assertEquals('newusername', $updatedUser->name);
    }

    /**
     * Firebase ログイン時に存在しないユーザーで 404 を返す
     */
    public function test_firebase_login_with_nonexistent_user_returns_404()
    {
        $firebaseUid = 'nonexistent-uid';
        $email = 'nonexistent@example.com';

        $this->mockFirebaseVerification($firebaseUid, $email);

        $response = $this->postJson('/api/login/firebase', [], [
            'Authorization' => 'Bearer valid-token',
        ]);

        $response->assertStatus(404)
                 ->assertJson(['error' => 'User not found']);
    }

    /**
     * Firebase ログイン時に存在するユーザーで 200 を返す
     */
    public function test_firebase_login_with_existing_user_returns_200()
    {
        $firebaseUid = 'existing-firebase-uid';
        $email = 'existing@example.com';

        // ユーザーを事前作成
        $user = User::create([
            'name' => 'testuser',
            'email' => $email,
            'firebase_uid' => $firebaseUid,
        ]);

        $this->mockFirebaseVerification($firebaseUid, $email);

        $response = $this->postJson('/api/login/firebase', [], [
            'Authorization' => 'Bearer valid-token',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Login OK']);

        // レスポンス内の user オブジェクトが期待される形式であることを確認
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'firebase_uid'],
        ]);
    }

    /**
     * API レスポンスに password や remember_token が含まれていないことを確認
     */
    public function test_api_response_does_not_contain_sensitive_fields()
    {
        $firebaseUid = 'test-uid';
        $email = 'test@example.com';

        $user = User::create([
            'name' => 'testuser',
            'email' => $email,
            'firebase_uid' => $firebaseUid,
        ]);

        $this->mockFirebaseVerification($firebaseUid, $email);

        $response = $this->postJson('/api/login/firebase', [], [
            'Authorization' => 'Bearer valid-token',
        ]);

        // password と remember_token フィールドが含まれていないことを確認
        $response->assertJsonMissing([
            'password' => null,
            'remember_token' => null,
        ]);

        // JSON レスポンス全体をチェック
        $jsonResponse = $response->json();
        if (isset($jsonResponse['user'])) {
            $this->assertArrayNotHasKey('password', $jsonResponse['user']);
            $this->assertArrayNotHasKey('remember_token', $jsonResponse['user']);
        }
    }
}