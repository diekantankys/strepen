<?php

namespace Tests\Feature\Resources;

use App\Models\ApiKey;
use App\Models\Inventory;
use App\Models\Post;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\NewPost;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ResourceVisibilityTest extends TestCase
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

    public function test_product_resource_hides_inventory_fields_for_normal_users_and_shows_them_for_managers()
    {
        $normal = User::factory()->create();
        $manager = User::factory()->manager()->create();
        $product = Product::factory()->create(['amount' => 12, 'active' => false]);

        $normalResponse = $this->authGet($normal, 'api.products.show', ['product' => $product])->assertOk();
        $this->assertArrayNotHasKey('inventory_amount', $normalResponse->json());
        $this->assertArrayNotHasKey('active', $normalResponse->json());

        Auth::forgetGuards();
        $managerResponse = $this->authGet($manager, 'api.products.show', ['product' => $product])->assertOk();
        $managerProduct = $managerResponse->json('data') ?? $managerResponse->json();
        $this->assertSame(12, $managerProduct['inventory_amount']);
        $this->assertFalse($managerProduct['active']);
    }

    public function test_inventory_and_transaction_resources_include_manager_only_updated_at()
    {
        $manager = User::factory()->manager()->create();
        $product = Product::factory()->create();
        $inventory = new Inventory;
        $inventory->user_id = $manager->id;
        $inventory->name = 'Visible inventory';
        $inventory->price = 2;
        $inventory->save();
        $inventory->products()->attach($product, ['price' => 1, 'amount' => 2]);
        $transaction = Transaction::factory()->for($manager)->create();
        $transaction->products()->attach($product, ['price' => 1, 'amount' => 1]);

        $this->authGet($manager, 'api.inventories.show', ['inventory' => $inventory])->assertOk()
            ->assertJsonStructure(['updated_at', 'products' => [['amount']]]);

        $this->authGet($manager, 'api.transactions.show', ['transaction' => $transaction])->assertOk()
            ->assertJsonStructure(['updated_at', 'products' => [['amount']]]);
    }

    public function test_post_and_notification_resources_include_reaction_and_post_context()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create(['body' => '[Example](https://example.com)']);
        $post->like($user);
        $user->notify(new NewPost($user, $post));

        $this->authGet($user, 'api.posts.show', ['post' => $post])->assertOk()
            ->assertJsonPath('likes', 1)
            ->assertJsonPath('user_liked', true)
            ->assertJsonPath('dislikes', 0)
            ->assertJsonPath('user_disliked', false)
            ->assertJsonFragment(['body' => '<p><a href="https://example.com" target="_blank" rel="noreferrer">Example</a></p>']);

        $notification = $user->notifications()->first();
        $this->authGet($user, 'api.users.show_notifications', ['user' => $user])->assertOk()
            ->assertJsonPath('data.0.id', $notification->id)
            ->assertJsonPath('data.0.type', 'new_post')
            ->assertJsonPath('data.0.post.id', $post->id);
    }
}
