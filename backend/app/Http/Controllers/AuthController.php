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
        $request->validate([
            'username' => 'required|max:20',
            'email'    => 'required|email',
        ]);

        $firebase = $this->getFirebaseUidFromToken($request);
        if (isset($firebase['error'])) {
            return response()->json(['error' => $firebase['error']], 401);
        }

        $firebaseUid = $firebase['uid'];
        $email = $request->email;

        \Log::info("[AuthController] Firebase UID={$firebaseUid}, email={$email}");

        // firebase_uid or email いずれか一致で取得
        $user = User::where('firebase_uid', $firebaseUid)
                    ->orWhere('email', $email)
                    ->first();

        if (!$user) {
            $user = User::create([
                'name'         => $request->username,
                'email'        => $email,
                'firebase_uid' => $firebaseUid,
                'password'     => '',
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
        \Log::info('[AuthController] Firebase logout');

        return response()->json([
            'message' => 'Logout successful',
        ], 200);
    }
}