<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FirebaseAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // テスト環境はスキップ
        if (app()->environment('testing')) {
            $request->attributes->set('firebase_user', (object)[
                'uid'   => $request->header('X-Test-Firebase-UID', 'test-firebase-uid'),
                'email' => $request->header('X-Test-Email', 'test@example.com'),
            ]);
            return $next($request);
        }

        $authHeader = $request->header('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $auth = app('firebase.auth');
            $verified = $auth->verifyIdToken($matches[1]);

            $request->attributes->set('firebase_user', (object)[
                'uid'   => $verified->claims()->get('sub'),
                'email' => $verified->claims()->get('email'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
