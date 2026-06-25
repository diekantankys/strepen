<?php

namespace Tests\Feature\Api;

use App\Models\ApiKey;
use App\Models\User;
use Tests\TestCase;

class ApiAuthAndMiddlewareTest extends TestCase
{
    public function test_api_home()
    {
        $this->getJson('/api')->assertOk()
            ->assertJsonPath('message', 'Strepen REST API documentation: https://github.com/diekantankys/strepen/blob/master/docs/api.md');
    }

    public function test_api_key_is_required()
    {
        $this->getJson(route('api.auth.login'))->assertStatus(400)
            ->assertJsonValidationErrors('api_key');
    }

    public function test_inactive_api_key_is_rejected()
    {
        $apiKey = ApiKey::first();
        $apiKey->active = false;
        $apiKey->save();

        $this->getJson(route('api.auth.login', ['api_key' => $apiKey->key]))
            ->assertStatus(400)
            ->assertJsonPath('errors.api_key', 'This api key is not active');
    }

    public function test_api_key_request_counter_increments()
    {
        $apiKey = ApiKey::first();

        $this->getJson(route('api.auth.login', ['api_key' => $apiKey->key]))
            ->assertStatus(400);

        $this->assertSame(1, $apiKey->fresh()->requests);
    }

    public function test_api_login_and_logout_revoke_token()
    {
        $apiKey = ApiKey::first();
        $password = 'secret123';
        $user = User::factory()->password($password)->create();

        $login = $this->postJson(route('api.auth.login'), [
            'api_key' => $apiKey->key,
            'email' => $user->email,
            'password' => $password,
        ])->assertOk()
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonStructure(['token', 'user' => ['id', 'firstname', 'lastname']]);

        $token = $login->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.auth.logout', ['api_key' => $apiKey->key]))
            ->assertOk()
            ->assertJsonPath('message', 'Your current auth token has been signed out');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_api_login_rejects_inactive_and_deleted_users()
    {
        $apiKey = ApiKey::first();
        $password = 'secret123';

        $inactiveUser = User::factory()->password($password)->create(['active' => false]);
        $this->postJson(route('api.auth.login'), [
            'api_key' => $apiKey->key,
            'email' => $inactiveUser->email,
            'password' => $password,
        ])->assertStatus(400);

        $deletedUser = User::factory()->password($password)->create();
        $deletedUser->delete();
        $this->postJson(route('api.auth.login'), [
            'api_key' => $apiKey->key,
            'email' => $deletedUser->email,
            'password' => $password,
        ])->assertStatus(400);
    }
}
