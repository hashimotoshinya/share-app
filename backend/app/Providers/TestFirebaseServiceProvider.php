<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * テスト環境用の Firebase Service Provider
 * 実際の Firebase 認証を使わず、モック機能を提供します
 */
class TestFirebaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        // テスト環境では Firebase Auth を null で返す
        // ミドルウェアで環境検出して処理がスキップされる
        $this->app->singleton('firebase.auth', function () {
            return null;
        });
    }

    public function boot()
    {
        //
    }
}
