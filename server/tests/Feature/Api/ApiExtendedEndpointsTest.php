<?php

namespace Tests\Feature\Api;

use App\Models\ApiKey;
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
        return $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($user))
            ->getJson(route($route, array_merge(['api_key' => $this->apiKey()], $params)));
    }

    private function authPost(User $user, string $route, array $params = [], array $payload = [])
    {
        return $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($user))
            ->postJson(route($route, array_merge(['api_key' => $this->apiKey()], $params)), $payload);
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
            'receive_news' => 'false',
            'current_password' => 'old-secret',
            'password' => 'new-secret',
            'password_confirmation' => 'new-secret',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            'thanks' => UploadedFile::fake()->image('thanks.gif'),
        ])->assertOk()
            ->assertJsonPath('user.firstname', 'Edited')
            ->assertJsonPath('user.email', 'edited@example.com')
            ->assertJsonPath('user.receive_news', false);

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
