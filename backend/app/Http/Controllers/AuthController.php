<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Firebase の ID Token を検証し、ユーザー情報を取り出す共通メソッド
     */
    private function getFirebaseUidFromToken(Request $request)
    {
        $idToken = $request->bearerToken();

        if (!$idToken) {
            return ['error' => 'Missing ID token'];
        }

        try {
            $verified = app('firebase.auth')->verifyIdToken($idToken);
            return [
                'uid' => $verified->claims()->get('sub'),
                'email' => $verified->claims()->get('email'),
            ];
        } catch (\Exception $e) {
            \Log::warning('[AuthController] Token verify failed: ' . $e->getMessage());
            return ['error' => 'Invalid ID token'];
        }
    }

    /**
     * Firebase 登録
     */
    public function registerFromFirebase(Request $request)
    {
        // 入力として必要なのはこれだけ
        $request->validate([
            'username' => 'required|max:20',
            'email'    => 'required|email',
        ]);

        // FirebaseAuth ミドルウェアが注入した情報を取得
        $firebaseUser = $request->attributes->get('firebase_user');

        if (!$firebaseUser) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $firebaseUid = $firebaseUser->uid;
        $email       = $firebaseUser->email;

        $user = User::where('firebase_uid', $firebaseUid)
                    ->orWhere('email', $email)
                    ->first();

        if (!$user) {
            $user = User::create([
                'name'         => $request->username,
                'email'        => $email,
                'firebase_uid' => $firebaseUid,
            ]);
        } else {
            // 既存ユーザーは name を更新（要件どおり）
            $user->update([
                'name' => $request->username,
            ]);
        }

        return response()->json([
            'message' => 'User registered/verified',
            'user' => $user,
        ]);
    }

    /**
     * Firebase ログイン
     */
    public function firebaseLogin(Request $request)
    {
        $firebase = $this->getFirebaseUidFromToken($request);
        if (isset($firebase['error'])) {
            return response()->json(['error' => $firebase['error']], 401);
        }

        $user = User::where('firebase_uid', $firebase['uid'])->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'message' => 'Login OK',
            'user' => $user
        ]);
    }

    /**
     * Firebase Logout
     */
    public function firebaseLogout()
    {
        return response()->json([
            'message' => 'Logout successful',
        ], 200);
    }
}