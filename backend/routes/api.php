<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthCheckController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LikeController;

// 動作確認用
Route::get('/ping', fn() => response()->json(['message' => 'API OK']));

// Firebase ログイン
Route::post('/login/firebase', [AuthController::class, 'firebaseLogin']);

// Firebase ログアウト
Route::post('/logout/firebase', [AuthController::class, 'firebaseLogout']);


// ▼ ここから認証必須 ▼
Route::middleware('firebase.auth')->group(function () {

    // 認証チェック
    Route::get('/auth-check', [AuthCheckController::class, 'check']);

    // Firebase 登録
    Route::post('/register/firebase', [AuthController::class, 'registerFromFirebase']);

    // 投稿
    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);

    // コメント
    Route::get('/posts/{post_id}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{post_id}/comments', [CommentController::class, 'store']);

    // いいね
    Route::post('/posts/{id}/like', [LikeController::class, 'toggle']);
});