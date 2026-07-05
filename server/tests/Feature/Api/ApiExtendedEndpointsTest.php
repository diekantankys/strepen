<?php

namespace Tests\Feature\Api;

use App\Models\ApiKey;
use App\Models\FcmToken;
use App\Models\Inventory;
use App\Models\Post;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\LowBalance;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiExtendedEndpointsTest extends TestCase
{
    private function apiKey(): string
    {
        return ApiKey::first()->key;
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test token')->plainTextToken;
    }

    private function authGet(User $user, string $route, array $params = [])
    {
        return $this->getJson(
            route($route, array_merge(['api_key' => $this->apiKey()], $params)),
            ['Authorization' => 'Bearer '.$this->tokenFor($user)],
        );
    }

    private function authPost(User $user, string $route, array $params = [], array $payload = [])
    {
        return $this->postJson(
            route($route, array_merge(['api_key' => $this->apiKey()], $params)),
            $payload,
            ['Authorization' => 'Bearer '.$this->tokenFor($user)],
        );
    }

    private function authDelete(User $user, string $route, array $params = [], array $payload = [])
    {
        return $this->deleteJson(
            route($route, array_merge(['api_key' => $this->apiKey()], $params)),
            $payload,
            ['Authorization' => 'Bearer '.$this->tokenFor($user)],
        );
    }

    public function test_user_edit_updates_profile_password_and_uploaded_assets()
    {
        Storage::fake();
        $user = User::factory()->password('old-secret')->create();

        $this->authPost($user, 'api.users.edit', ['user' => $user], [
            'firstname' => 'Edited',
            'lastname' => 'Member',
            'email' => 'edited@example.com',
            'gender' => 'other',
            'birthday' => '2001-02-03',
            'language' => 'en',
            'theme' => 'dark',
            'notify_new_posts' => 'false',
            'current_password' => 'old-secret',
            'password' => 'new-secret',
            'password_confirmation' => 'new-secret',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            'thanks' => UploadedFile::fake()->image('thanks.gif'),
        ])->assertOk()
            ->assertJsonPath('user.firstname', 'Edited')
            ->assertJsonPath('user.email', 'edited@example.com')
            ->assertJsonPath('user.notify_new_posts', false);

        $user = $user->fresh();
        $this->assertTrue(Hash::check('new-secret', $user->password));
        $this->assertNotNull($user->avatar);
        $this->assertNotNull($user->thanks);

        $this->authPost($user, 'api.users.edit', ['user' => $user], [
            'avatar' => 'null',
            'thanks' => 'null',
        ])->assertOk();

        $this->assertNull($user->fresh()->avatar);
        $this->assertNull($user->fresh()->thanks);
    }

    public function test_api_inventory_and_user_inventory_endpoints_include_products()
    {
        $manager = User::factory()->manager()->create();
        $product = Product::factory()->create(['price' => 3.25]);
        $inventory = new Inventory;
        $inventory->user_id = $manager->id;
        $inventory->name = 'Restock delivery';
        $inventory->price = 13;
        $inventory->save();
        $inventory->products()->attach($product, ['price' => 3.25, 'amount' => 4]);

        $this->authGet($manager, 'api.inventories.index', ['query' => 'Restock'])
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Restock delivery')
            ->assertJsonPath('data.0.products.0.id', $product->id)
            ->assertJsonPath('data.0.products.0.amount', 4);

        $this->authGet($manager, 'api.users.show_inventories', ['user' => $manager])
            ->assertOk()
            ->assertJsonPath('data.0.id', $inventory->id);
    }

    public function test_api_notifications_list_unread_and_read()
    {
        $user = User::factory()->create(['balance' => -5]);
        $user->notify(new LowBalance($user));
        $notification = $user->notifications()->first();

        $this->authGet($user, 'api.users.show_notifications', ['user' => $user])
            ->assertOk()
            ->assertJsonPath('data.0.type', 'low_balance')
            ->assertJsonPath('data.0.data.balance', -5);

        $this->authGet($user, 'api.users.show_unread_notifications', ['user' => $user])
            ->assertOk()
            ->assertJsonPath('data.0.id', $notification->id);

        $this->authGet($user, 'api.notifications.read', ['notification' => $notification])
            ->assertOk()
            ->assertJsonPath('message', 'The notification is successfully read');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_all_notification_preference_fields_appear_and_can_be_updated()
    {
        $user = User::factory()->create([
            'notify_new_posts' => true,
            'notify_low_balance' => true,
            'notify_new_deposits' => true,
            'notify_new_transactions' => false,
            'notify_by_email' => true,
        ]);

        // All 5 fields appear in the user response
        $this->authGet($user, 'api.users.show', ['user' => $user])
            ->assertOk()
            ->assertJsonPath('notify_new_posts', true)
            ->assertJsonPath('notify_low_balance', true)
            ->assertJsonPath('notify_new_deposits', true)
            ->assertJsonPath('notify_new_transactions', false)
            ->assertJsonPath('notify_by_email', true);

        // All 5 fields can be updated via the edit endpoint
        $this->authPost($user, 'api.users.edit', ['user' => $user], [
            'notify_new_posts' => 'false',
            'notify_low_balance' => 'false',
            'notify_new_deposits' => 'false',
            'notify_new_transactions' => 'true',
            'notify_by_email' => 'false',
        ])->assertOk()
            ->assertJsonPath('user.notify_new_posts', false)
            ->assertJsonPath('user.notify_low_balance', false)
            ->assertJsonPath('user.notify_new_deposits', false)
            ->assertJsonPath('user.notify_new_transactions', true)
            ->assertJsonPath('user.notify_by_email', false);

        $user = $user->fresh();
        $this->assertFalse($user->notify_new_posts);
        $this->assertFalse($user->notify_low_balance);
        $this->assertFalse($user->notify_new_deposits);
        $this->assertTrue($user->notify_new_transactions);
        $this->assertFalse($user->notify_by_email);
    }

    public function test_fcm_token_can_be_registered_reassigned_and_removed()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        FcmToken::create([
            'user_id' => $otherUser->id,
            'token' => 'device-token',
        ]);

        $this->authPost($user, 'api.users.store_fcm_token', ['user' => $user], [
            'fcm_token' => 'device-token',
        ])->assertOk()
            ->assertJsonPath('message', 'FCM token registered');

        $this->assertSame(1, FcmToken::where('token', 'device-token')->count());
        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $user->id,
            'token' => 'device-token',
        ]);

        FcmToken::create([
            'user_id' => $otherUser->id,
            'token' => 'other-device-token',
        ]);

        $this->authDelete($user, 'api.users.destroy_fcm_token', ['user' => $user], [
            'fcm_token' => 'other-device-token',
        ])->assertOk()
            ->assertJsonPath('message', 'FCM token removed');

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $otherUser->id,
            'token' => 'other-device-token',
        ]);

        $this->authDelete($user, 'api.users.destroy_fcm_token', ['user' => $user], [
            'fcm_token' => 'device-token',
        ])->assertOk();

        $this->assertDatabaseMissing('fcm_tokens', [
            'token' => 'device-token',
        ]);
    }

    public function test_fcm_token_endpoints_validate_required_token()
    {
        $user = User::factory()->create();

        $this->authPost($user, 'api.users.store_fcm_token', ['user' => $user])
            ->assertStatus(400)
            ->assertJsonValidationErrors('fcm_token');

        $this->authDelete($user, 'api.users.destroy_fcm_token', ['user' => $user])
            ->assertStatus(400)
            ->assertJsonValidationErrors('fcm_token');
    }

    public function test_user_posts_and_transaction_show_endpoints_return_related_resources()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create(['body' => '[link](https://example.com)']);
        $transaction = Transaction::factory()->for($user)->create(['name' => 'Single transaction']);
        $product = Product::factory()->create();
        $transaction->products()->attach($product, ['price' => 1.5, 'amount' => 2]);

        $this->authGet($user, 'api.users.show_posts', ['user' => $user])
            ->assertOk()
            ->assertJsonPath('data.0.id', $post->id)
            ->assertJsonPath('data.0.user_liked', false);

        $this->authGet($user, 'api.transactions.show', ['transaction' => $transaction])
            ->assertOk()
            ->assertJsonPath('name', 'Single transaction')
            ->assertJsonPath('products.0.id', $product->id)
            ->assertJsonPath('products.0.amount', 2);
    }
}
