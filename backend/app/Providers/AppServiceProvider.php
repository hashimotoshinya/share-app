<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // テスト環境では Firebase を読み込まない
        if (!app()->environment('testing')) {
            $this->app->register(\Kreait\Laravel\Firebase\ServiceProvider::class);
        } else {
            // テスト環境では Firebase Auth を null でモック
            $this->app->singleton('firebase.auth', function () {
                return null;
            });
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
