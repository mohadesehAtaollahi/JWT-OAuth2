<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request) {
        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = new User;
        $user->name = request()->name;
        $user->email = request()->email;
        $user->password = bcrypt(request()->password);
        $user->save();

        return response()->json($user, 201);
    }


    public function login(Request $request)
    {
        $credentials = request(['email', 'password']);

        if (!$access_token = auth()->claims(['token_type' => 'access'])->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $refresh_token = JWTAuth::claims(['token_type' => 'refresh'])->fromUser(auth()->user());

        return $this->respondWithToken($access_token, $refresh_token);
    }

    public function me(Request $request)
    {
        return response()->json(auth()->user());
    }

    public function refresh(Request $request)
    {
        $token = request()->cookie('refresh_token');
        $user = JWTAuth::setToken($token)->toUser();
        JWTAuth::setToken($token)->invalidate();

        $access_token = JWTAuth::claims(['token_type' => 'access'])->fromUser($user);
        $refresh_token = JWTAuth::claims(['token_type' => 'refresh'])->fromUser($user);

        return $this->respondWithToken($access_token , $refresh_token);

    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out'])->withoutCookie('refresh_token');;
    }

    protected function respondWithToken($access_token, $refresh_token)
    {
        $cookie = cookie('refresh_token',
            $refresh_token,
            config('jwt.refresh_ttl'),
            null,
            null,
            true,
            true,
            false,
            'Strict');

        $response = response()->json([
            'access_token' => $access_token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl'),
            'user' => auth()->user(),
        ]);
        return $response->withCookie($cookie);
    }
}
