<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Auth;
use Symfony\Component\HttpFoundation\Response;

class FirebaseAuth
{
    protected $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function handle(Request $request, Closure $next): Response
    {
        \Log::info('[FirebaseAuth] START');

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
            $verified = $this->auth->verifyIdToken($idToken);

            $uid   = $verified->claims()->get('sub');
            $email = $verified->claims()->get('email');

            \Log::info("[FirebaseAuth] Verified OK - UID={$uid}, EMAIL={$email}");

            // ← user プロパティを汚さず attributes に保存
            $request->attributes->set('firebase_user', (object)[
                'uid'   => $uid,
                'email' => $email,
            ]);

        } catch (\Throwable $e) {
            \Log::error('[FirebaseAuth] FAILED: ' . $e->getMessage());
            return response()->json([
                'message' => 'Invalid or expired token',
                'error'   => $e->getMessage(),
            ], 401);
        }

        \Log::info('[FirebaseAuth] END');
        return $next($request);
    }
}