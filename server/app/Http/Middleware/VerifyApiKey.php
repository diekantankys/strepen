<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Illuminate\Support\Facades\Validator;

class VerifyApiKey
{
    public function handle($request, $next, $type)
    {
        // Verify API key from HTTP header or GET / POST value
        $apiKey = $request->header('X-Api-Key', $request->input('api_key'));
        $validation = Validator::make(['api_key' => $apiKey], [
            'api_key' => 'required|exists:api_keys,key',
        ]);
        if ($validation->fails()) {
            return response(['errors' => $validation->errors()], 400);
        }

        // Increment API key requests counter
        $apiKey = ApiKey::where('key', $apiKey)->first();
        if ($apiKey->deleted_at != null) {
            return response(['errors' => ['api_key' => 'This api key is deleted']], 400);
        }
        if (! $apiKey->active) {
            return response(['errors' => ['api_key' => 'This api key is not active']], 400);
        }
        $apiKey->requests++;
        $apiKey->save();

        // Check auth token when needed
        if ($type != 'guest') {
            return app(Authenticate::class)->handle($request, function ($request) use ($type, $next) {
                // Check self
                if ($type == 'self') {
                    $parms = $request->route()->parameters();
                    $parmType = array_key_first($parms);
                    $user_id = $parmType == 'user' ? $parms[$parmType]->id : ($parmType == 'notification' ? $parms[$parmType]->notifiable_id : $parms[$parmType]->user_id);
                    if ($request->user()->normal && $user_id != $request->user()->id) {
                        return response(['errors' => [
                            'token' => 'You can only view your own data',
                        ]], 403);
                    }
                }

                // Check manager
                if ($type == 'manager') {
                    if (! $request->user()->manager) {
                        return response(['errors' => [
                            'token' => 'The authed user is not a manager or an admin',
                        ]], 403);
                    }
                }

                // Check admin
                if ($type == 'admin') {
                    if (! $request->user()->admin) {
                        return response(['errors' => [
                            'token' => 'The authed user is not an admin',
                        ]], 403);
                    }
                }

                // Go to next middleware
                return $next($request);
            }, 'sanctum');
        }

        // Go to next middleware
        return $next($request);
    }
}
