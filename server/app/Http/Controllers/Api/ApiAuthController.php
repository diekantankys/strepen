<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ApiAuthController extends Controller
{
    // API login route
    public function login(Request $request)
    {
        // Validate input
        $validation = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required',
        ]);
        if ($validation->fails()) {
            return response(['errors' => $validation->errors()], 400);
        }

        // Check if user is active and not deleted
        $user = User::withTrashed()->where('email', request('email'))->first();
        if ($user->deleted_at != null) {
            return response(['errors' => [__('auth.login.deleted_error')]], 400);
        }
        if (! $user->active) {
            return response(['errors' => [__('auth.login.active_error')]], 400);
        }

        // Try to login
        if (! Hash::check(request('password'), $user->password)) {
            return response(['errors' => [
                'email' => [__('auth.login.login_error')],
            ]], 403);
        }

        // Set request user
        $request->setUserResolver(fn () => $user);

        // Try to login user
        return [
            'token' => $user->createToken('API auth token for api')->plainTextToken,
            'user_id' => $user->id, // For backwards compatability
            'user' => new UserResource($user),
        ];
    }

    // API logout route
    public function logout(Request $request)
    {
        // Revoke current used token
        $request->user()->currentAccessToken()->delete();

        // Return success message
        return [
            'message' => 'Your current auth token has been signed out',
        ];
    }
}
