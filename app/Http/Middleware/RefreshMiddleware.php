<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;

class RefreshMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if ($request->hasCookie('refresh_token')) {
                JWTAuth::setToken($request->cookie('refresh_token'));
            }

            $tokenType = JWTAuth::getPayload()->get('token_type');
            if ($tokenType !== 'refresh') {
                return response()->json([
                    'error' => 'Invalid token type! Refresh token was expected.',
                ], 401);
            }

        }

        catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token has expired'], 401);
        }
        catch (JWTException $e) {
            return response()->json(['error' => "Token missing"], 401);
        }
        return $next($request);
    }
}
