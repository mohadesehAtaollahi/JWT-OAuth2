<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class MeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            JWTAuth::parseToken();

            $tokenType = JWTAuth::getPayload()->get('token_type');
            if ($tokenType !== 'access') {
                return response()->json([
                    'error' => 'Invalid token type! Access token was expected.',
                ], 401);
            }

            $user = JWTAuth::authenticate();
            if (!$user) {
                return response()->json(['error' => 'user not found'], 401);
            }
            auth()->setUser($user);
        }
        catch (TokenInvalidException $e) {
            return response()->json(['error' => 'Access token is invalid.'], 401);
        }
        catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token has expired.'], 401);
        }
        catch (JWTException $e) {
            return response()->json(['error' => 'Token missing!'], 401);
        }
        return $next($request);
    }
}
