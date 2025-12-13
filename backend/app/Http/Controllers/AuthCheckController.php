<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AuthCheckController extends Controller
{
    public function check(Request $request)
    {
        // Firebase ミドルウェアがセットしたユーザー情報
        $firebaseUser = $request->user;

        if (!$firebaseUser) {
            return response()->json([
                'message' => 'Unauthorized (No Firebase User)'
            ], 401);
        }

        // UID と email を取り出す（オブジェクトとして）
        $uid = $firebaseUser->uid;
        $email = $firebaseUser->email;

        // firebase_uid で検索
        $user = User::where('firebase_uid', $uid)->first();

        // 未登録なら作成
        if (!$user) {
            $user = User::create([
                'firebase_uid' => $uid,
                'email'        => $email,
                'name'         => $email,
            ]);
        }

        return response()->json([
            'message'          => 'Authentication OK',
            'firebase_uid'     => $user->firebase_uid,
            'email'            => $user->email,
            'laravel_user_id'  => $user->id,
        ]);
    }
}