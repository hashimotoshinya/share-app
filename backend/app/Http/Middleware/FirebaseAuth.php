<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FirebaseAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        \Log::info('[FirebaseAuth] START');

        // ★ テスト環境では Firebase 検証をスキップ
        if (app()->environment('testing')) {
            \Log::info('[FirebaseAuth] Testing mode - skipping Firebase verification');
            
            // テストで X-Test-Firebase-UID ヘッダーが指定されている場合はそれを使う
            $firebaseUid = $request->header('X-Test-Firebase-UID', 'test-firebase-uid');
            $email = $request->header('X-Test-Email', 'test@example.com');
            
            $request->attributes->set('firebase_user', (object)[
                'uid'   => $firebaseUid,
                'email' => $email,
            ]);
            return $next($request);
        }

        // Authorization: Bearer xxx
        $authHeader = $request->header('Authorization');
        \Log::info('[FirebaseAuth] Authorization=' . $authHeader);

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            \Log::warning('[FirebaseAuth] Token not provided');
            return response()->json(['message' => 'Token not provided'], 401);
        }

        $idToken = $matches[1];

        try {
            \Log::info('[FirebaseAuth] Verifying ID token...');
            $auth = app('firebase.auth');
            $verified = $auth->verifyIdToken($idToken);

            $uid   = $verified->claims()->get('sub');
            $email = $verified->claims()->get('email');

            $request->attributes->set('firebase_user', (object)[
                'uid'   => $uid,
                'email' => $email,
            ]);

            \Log::info("[FirebaseAuth] Verified: uid=$uid, email=$email");

        } catch (\Exception $e) {
            \Log::warning('[FirebaseAuth] Error: ' . $e->getMessage());
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
