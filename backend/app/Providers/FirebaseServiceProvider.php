<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('firebase.auth', function () {
            // テスト環境では Firebase 初期化をスキップ
            if (app()->environment('testing')) {
                return null;
            }

            $credentialsPath = env('FIREBASE_CREDENTIALS');

            $serviceAccount = json_decode(file_get_contents($credentialsPath), true);

            return (new Factory)
                ->withServiceAccount($serviceAccount)
                ->createAuth();
        });
    }

    public function boot()
    {
        //
    }
}