<?php

namespace Tests\Feature\Api;

use App\Models\ApiKey;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Tests\TestCase;

class ApiResourcesTest extends TestCase
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

    private function authPut(User $user, string $route, array $params = [])
    {
        return $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($user))
            ->putJson(route($route, array_merge(['api_key' => $this->apiKey()], $params)));
    }

    private function authDelete(User $user, string $route, array $params = [])
    {
        return $this->flushHeaders()
            ->withHeader('Authorization', 'Bearer '.$this->tokenFor($user))
            ->deleteJson(route($route, array_merge(['api_key' => $this->apiKey()], $params)));
    }

    public function test_normal_users_only_see_active_users_and_public_fields()
    {
        $user = User::factory()->create();
        $activeOther = User::factory()->create([
            'firstname' => 'Visible',
            'lastname' => 'Aardvark',
            'active' => true,
        ]);
        $inactiveOther = User::factory()->create([
            'firstname' => 'Hidden',
            'lastname' => 'Aardvark',
            'active' => false,
        ]);

        $response = $this->authGet($user, 'api.users.index', ['query' => 'Aardvark'])->assertOk();

        $users = collect($response->json('data') ?? $response->json());
        $ids = $users->pluck('id');
        $this->assertTrue($ids->contains($activeOther->id));
        $this->assertFalse($ids->contains($inactiveOther->id));

        $shownOther = $users->firstWhere('id', $activeOther->id);
        $this->assertArrayNotHasKey('email', $shownOther);
        $this->assertArrayNotHasKey('active', $shownOther);
    }

    public function test_managers_see_inactive_users_and_manager_fields()
    {
        $manager = User::factory()->manager()->create();
        $inactiveUser = User::factory()->create(['active' => false]);

        $response = $this->authGet($manager, 'api.users.index')->assertOk();

        $shownUser = collect($response->json('data'))->firstWhere('id', $inactiveUser->id);
        $this->assertSame($inactiveUser->email, $shownUser['email']);
        $this->assertFalse($shownUser['active']);
    }

    public function test_self_routes_reject_other_normal_users()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->authGet($user, 'api.users.show_transactions', ['user' => $otherUser])
            ->assertForbidden()
            ->assertJsonPath('errors.token', 'You can only view your own data');
    }

    public function test_manager_routes_reject_normal_users()
    {
        $user = User::factory()->create();

        $this->authGet($user, 'api.transactions.index')
            ->assertForbidden()
            ->assertJsonPath('errors.token', 'The authed user is not a manager or an admin');
    }

    public function test_products_index_filters_inactive_for_normal_users()
    {
        $user = User::factory()->create();
        $activeProduct = Product::factory()->create(['name' => 'Cola', 'active' => true]);
        $inactiveProduct = Product::factory()->create(['name' => 'Old Soda', 'active' => false]);

        $response = $this->authGet($user, 'api.products.index', ['query' => 'o', 'limit' => 100])
            ->assertOk()
            ->assertJsonPath('meta.per_page', 50);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($activeProduct->id));
        $this->assertFalse($ids->contains($inactiveProduct->id));
    }

    public function test_settings_endpoint_casts_values_and_parses_product_ids()
    {
        Setting::set('currency_symbol', '€');
        Setting::set('min_user_balance', 20);
        Setting::set('max_stripe_amount', 24);
        Setting::set('leaderboards_enabled', 'true');
        Setting::set('product_beer_ids', '1, 2,foo,0,3');
        $user = User::factory()->create();

        $this->authGet($user, 'api.settings.index')->assertOk()
            ->assertJsonPath('currency_symbol', '€')
            ->assertJsonPath('min_user_balance', 20)
            ->assertJsonPath('max_stripe_amount', 24)
            ->assertJsonPath('leaderboards_enabled', true)
            ->assertJsonPath('product_beer_ids', [1, 2, 3]);
    }

    public function test_posts_can_be_liked_disliked_and_toggled()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for(User::factory())->create();

        $this->authGet($user, 'api.posts.like', ['post' => $post])->assertOk();
        $this->assertDatabaseHas('post_likes', ['post_id' => $post->id, 'user_id' => $user->id]);

        $this->authGet($user, 'api.posts.dislike', ['post' => $post])->assertOk();
        $this->assertDatabaseMissing('post_likes', ['post_id' => $post->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('post_dislikes', ['post_id' => $post->id, 'user_id' => $user->id]);

        $this->authGet($user, 'api.posts.dislike', ['post' => $post])->assertOk();
        $this->assertDatabaseMissing('post_dislikes', ['post_id' => $post->id, 'user_id' => $user->id]);
    }

    public function test_comment_dislike_replaces_a_like_in_the_response()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for(User::factory())->create();
        $comment = new PostComment;
        $comment->post_id = $post->id;
        $comment->user_id = User::factory()->create()->id;
        $comment->body = 'A comment';
        $comment->save();

        $this->authPut($user, 'api.post_comments.like', [
            'post' => $post,
            'comment' => $comment,
        ])->assertOk()
            ->assertJsonPath('user_liked', true)
            ->assertJsonPath('user_disliked', false);

        $this->authPut($user, 'api.post_comments.dislike', [
            'post' => $post,
            'comment' => $comment,
        ])->assertOk()
            ->assertJsonPath('likes', 0)
            ->assertJsonPath('user_liked', false)
            ->assertJsonPath('dislikes', 1)
            ->assertJsonPath('user_disliked', true);
    }

    public function test_comment_actions_and_replies_are_scoped_to_the_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for(User::factory())->create();
        $otherPost = Post::factory()->for(User::factory())->create();
        $otherComment = new PostComment;
        $otherComment->post_id = $otherPost->id;
        $otherComment->user_id = User::factory()->create()->id;
        $otherComment->body = 'Another post comment';
        $otherComment->save();

        $this->authPut($user, 'api.post_comments.like', [
            'post' => $post,
            'comment' => $otherComment,
        ])->assertNotFound();

        $this->authPost($user, 'api.post_comments.store', ['post' => $post], [
            'body' => 'Invalid reply',
            'parent_id' => $otherComment->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['parent_id']);
    }

    public function test_comments_cannot_be_replied_to_beyond_the_maximum_depth()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for(User::factory())->create();
        $parentId = null;

        for ($depth = 0; $depth <= PostComment::MAX_REPLY_DEPTH; $depth++) {
            $comment = new PostComment;
            $comment->post_id = $post->id;
            $comment->user_id = User::factory()->create()->id;
            $comment->parent_id = $parentId;
            $comment->body = 'Comment at depth '.$depth;
            $comment->save();
            $parentId = $comment->id;
        }

        $this->authPost($user, 'api.post_comments.store', ['post' => $post], [
            'body' => 'Too deep',
            'parent_id' => $parentId,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['parent_id']);
    }

    public function test_comment_replies_bump_thread_and_post_updated_at_for_sorting()
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->for($author)->create([
            'updated_at' => '2024-01-01 08:00:00',
        ]);
        $activeRoot = new PostComment;
        $activeRoot->post_id = $post->id;
        $activeRoot->user_id = $author->id;
        $activeRoot->body = 'Older thread with new reply';
        $activeRoot->created_at = '2024-01-01 09:00:00';
        $activeRoot->updated_at = '2024-01-01 09:00:00';
        $activeRoot->save();
        $staleRoot = new PostComment;
        $staleRoot->post_id = $post->id;
        $staleRoot->user_id = $author->id;
        $staleRoot->body = 'Newer but stale thread';
        $staleRoot->created_at = '2024-01-01 11:00:00';
        $staleRoot->updated_at = '2024-01-01 11:00:00';
        $staleRoot->save();

        $this->travelTo('2024-01-01 12:00:00');
        $this->authPost($user, 'api.post_comments.store', ['post' => $post], [
            'body' => 'Bump the thread',
            'parent_id' => $activeRoot->id,
        ])->assertCreated()
            ->assertJsonStructure(['updated_at']);

        $this->assertSame('2024-01-01 12:00:00', $activeRoot->fresh()->updated_at->format('Y-m-d H:i:s'));
        $this->assertSame('2024-01-01 12:00:00', $post->fresh()->updated_at->format('Y-m-d H:i:s'));

        $comments = $this->authGet($user, 'api.post_comments.index', ['post' => $post])
            ->assertOk()
            ->json();

        $this->assertSame($activeRoot->id, $comments[0]['id']);
        $this->assertSame($staleRoot->id, $comments[1]['id']);
        $this->assertArrayHasKey('updated_at', $comments[0]);
    }

    public function test_comment_owners_cannot_delete_comments_with_replies()
    {
        $owner = User::factory()->create();
        $post = Post::factory()->for(User::factory())->create();
        $parent = new PostComment;
        $parent->post_id = $post->id;
        $parent->user_id = $owner->id;
        $parent->body = 'Parent comment';
        $parent->save();
        $reply = new PostComment;
        $reply->post_id = $post->id;
        $reply->user_id = User::factory()->create()->id;
        $reply->parent_id = $parent->id;
        $reply->body = 'Reply';
        $reply->save();

        $this->authDelete($owner, 'api.post_comments.destroy', [
            'post' => $post,
            'comment' => $parent,
        ])->assertForbidden();
    }

    public function test_admins_can_delete_comments_with_replies()
    {
        $admin = User::factory()->admin()->create();
        $this->assertTrue($admin->fresh()->admin);
        $post = Post::factory()->for(User::factory())->create();
        $parent = new PostComment;
        $parent->post_id = $post->id;
        $parent->user_id = User::factory()->create()->id;
        $parent->body = 'Parent comment';
        $parent->save();
        $reply = new PostComment;
        $reply->post_id = $post->id;
        $reply->user_id = User::factory()->create()->id;
        $reply->parent_id = $parent->id;
        $reply->body = 'Reply';
        $reply->save();

        $this->authDelete($admin, 'api.post_comments.destroy', [
            'post' => $post,
            'comment' => $parent,
        ])->assertOk();
        $this->assertSoftDeleted('post_comments', ['id' => $parent->id]);
        $this->assertSoftDeleted('post_comments', ['id' => $reply->id]);
    }

    public function test_transaction_store_creates_pivots_and_updates_amounts_and_balance()
    {
        $user = User::factory()->create(['balance' => 30]);
        $product = Product::factory()->create(['price' => 2.5, 'amount' => 10]);

        $this->authPost($user, 'api.transactions.store', payload: [
            'name' => 'Bar order',
            'products' => [
                ['product_id' => $product->id, 'amount' => 3],
            ],
        ])->assertOk()
            ->assertJsonPath('transaction.name', 'Bar order')
            ->assertJsonPath('transaction.price', 7.5);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'name' => 'Bar order',
            'price' => 7.5,
        ]);
        $transaction = Transaction::where('name', 'Bar order')->first();
        $this->assertDatabaseHas('transaction_product', [
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'price' => 2.5,
            'amount' => 3,
        ]);
        $this->assertSame(7, $product->fresh()->amount);
        $this->assertSame(22.5, $user->fresh()->balance);
    }

    public function test_transaction_store_rejects_empty_products()
    {
        $user = User::factory()->create();

        $this->authPost($user, 'api.transactions.store', payload: [
            'name' => 'Empty order',
            'products' => [],
        ])->assertStatus(400)
            ->assertJsonPath('errors.products', 'You need to add minimal one product to the transaction');
    }
}
