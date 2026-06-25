<?php

namespace Tests\Feature\Models;

use App\Models\ApiKey;
use App\Models\Inventory;
use App\Models\Post;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class ModelBehaviorTest extends TestCase
{
    private function createInventory(User $user, Product $product, string $date, int $amount, float $price = 1): Inventory
    {
        $inventory = new Inventory;
        $inventory->user_id = $user->id;
        $inventory->name = 'Inventory '.$date;
        $inventory->price = $amount * $price;
        $inventory->created_at = $date;
        $inventory->updated_at = $date;
        $inventory->save();
        $inventory->products()->attach($product, ['price' => $price, 'amount' => $amount]);

        return $inventory;
    }

    private function createTransaction(User $user, string $date, int $type, float $price): Transaction
    {
        return Transaction::factory()->for($user)->create([
            'type' => $type,
            'price' => $price,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }

    public function test_user_recalculate_balance_uses_transaction_types_and_ignores_deleted_transactions()
    {
        $user = User::factory()->create(['balance' => 999]);

        Transaction::factory()->for($user)->create([
            'type' => Transaction::TYPE_DEPOSIT,
            'price' => 20,
        ]);
        Transaction::factory()->for($user)->create([
            'type' => Transaction::TYPE_TRANSACTION,
            'price' => 7.5,
        ]);
        Transaction::factory()->for($user)->create([
            'type' => Transaction::TYPE_PAYMENT,
            'price' => 2.5,
        ]);
        Transaction::factory()->for($user)->create([
            'type' => Transaction::TYPE_DEPOSIT,
            'price' => 100,
            'deleted_at' => now(),
        ]);

        $user->recalculateBalance();

        $this->assertSame(10.0, $user->balance);
    }

    public function test_system_user_recalculate_balance_stays_zero()
    {
        $systemUser = User::find(1);
        Transaction::factory()->for($systemUser)->create([
            'type' => Transaction::TYPE_DEPOSIT,
            'price' => 100,
        ]);

        $systemUser->recalculateBalance();

        $this->assertSame(0.0, $systemUser->balance);
    }

    public function test_product_recalculate_amount_uses_inventories_transactions_and_ignores_deleted_rows()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['amount' => 0]);

        $inventory = new Inventory;
        $inventory->user_id = $user->id;
        $inventory->name = 'Stock in';
        $inventory->price = 12;
        $inventory->save();
        $inventory->products()->attach($product, ['price' => 2, 'amount' => 6]);

        $deletedInventory = new Inventory;
        $deletedInventory->user_id = $user->id;
        $deletedInventory->name = 'Deleted stock';
        $deletedInventory->price = 50;
        $deletedInventory->save();
        $deletedInventory->products()->attach($product, ['price' => 5, 'amount' => 10]);
        $deletedInventory->delete();

        $transaction = Transaction::factory()->for($user)->create([
            'type' => Transaction::TYPE_TRANSACTION,
            'price' => 4,
        ]);
        $transaction->products()->attach($product, ['price' => 2, 'amount' => 2]);

        $deletedTransaction = Transaction::factory()->for($user)->create([
            'type' => Transaction::TYPE_TRANSACTION,
            'price' => 2,
        ]);
        $deletedTransaction->products()->attach($product, ['price' => 2, 'amount' => 1]);
        $deletedTransaction->delete();

        $product->recalculateAmount();

        $this->assertSame(4, $product->amount);
    }

    public function test_post_like_and_dislike_toggle_and_replace_each_other()
    {
        $user = User::factory()->create();
        $post = Post::factory()->for(User::factory())->create();

        $post->like($user);
        $this->assertDatabaseHas('post_likes', ['post_id' => $post->id, 'user_id' => $user->id]);

        $post->refresh()->dislike($user);
        $this->assertDatabaseMissing('post_likes', ['post_id' => $post->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('post_dislikes', ['post_id' => $post->id, 'user_id' => $user->id]);

        $post->refresh()->dislike($user);
        $this->assertDatabaseMissing('post_dislikes', ['post_id' => $post->id, 'user_id' => $user->id]);
    }

    public function test_system_user_cannot_like_or_dislike_posts()
    {
        $systemUser = User::find(1);
        $post = Post::factory()->for(User::factory())->create();

        $post->like($systemUser);
        $post->dislike($systemUser);

        $this->assertDatabaseMissing('post_likes', ['post_id' => $post->id, 'user_id' => $systemUser->id]);
        $this->assertDatabaseMissing('post_dislikes', ['post_id' => $post->id, 'user_id' => $systemUser->id]);
    }

    public function test_setting_get_set_and_missing_setting_exception()
    {
        Setting::set('currency_symbol', '€');
        $this->assertSame('€', Setting::get('currency_symbol'));

        Setting::set('currency_symbol', '$');
        $this->assertSame('$', Setting::get('currency_symbol'));

        $this->expectException(ModelNotFoundException::class);
        Setting::get('missing_setting');
    }

    public function test_user_accessors_and_search_helpers()
    {
        Setting::set('minor_age', 18);
        $minor = User::factory()->create([
            'firstname' => 'Alpha',
            'insertion' => 'van',
            'lastname' => 'Tester',
            'email' => 'alpha@example.com',
            'birthday' => now()->subYears(17)->format('Y-m-d'),
        ]);
        $manager = User::factory()->manager()->create(['firstname' => 'ManagerFind']);
        $admin = User::factory()->admin()->create(['firstname' => 'AdminFind']);

        $this->assertSame('Alpha van Tester', $minor->name);
        $this->assertTrue($minor->minor);
        $this->assertTrue($minor->normal);
        $this->assertTrue($manager->manager);
        $this->assertTrue($admin->admin);

        $this->assertTrue(User::search(User::query(), 'alpha@example.com')->pluck('id')->contains($minor->id));
    }

    public function test_search_helpers_find_records_by_main_text_fields()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Search Cola', 'description' => 'Sparkling']);
        $post = Post::factory()->for($user)->create(['title' => 'Search Post', 'body' => 'Body marker']);
        $inventory = $this->createInventory($user, $product, now()->format('Y-m-d H:i:s'), 1);
        $inventory->name = 'Search Inventory';
        $inventory->save();
        $transaction = Transaction::factory()->for($user)->create(['name' => 'Search Transaction']);
        $apiKey = new ApiKey;
        $apiKey->name = 'Search Api Key';
        $apiKey->key = ApiKey::generateKey();
        $apiKey->save();

        $this->assertTrue(Product::search(Product::query(), 'Sparkling')->pluck('id')->contains($product->id));
        $this->assertTrue(Post::search(Post::query(), 'Body marker')->pluck('id')->contains($post->id));
        $this->assertTrue(Inventory::search(Inventory::query(), 'Search Inventory')->pluck('id')->contains($inventory->id));
        $this->assertTrue(Transaction::search(Transaction::query(), 'Search Transaction')->pluck('id')->contains($transaction->id));
        $this->assertTrue(ApiKey::search(ApiKey::query(), 'Search Api Key')->pluck('id')->contains($apiKey->id));
    }

    public function test_generated_file_names_normalize_jpeg_extensions()
    {
        $this->assertStringEndsWith('.jpg', User::generateAvatarName('jpeg'));
        $this->assertStringEndsWith('.jpg', User::generateThanksName('jpg'));
        $this->assertStringEndsWith('.jpg', Product::generateImageName('jpeg'));
        $this->assertStringEndsWith('.jpg', Post::generateImageName('jpeg'));
    }

    public function test_user_balance_chart_tracks_daily_balance_changes()
    {
        $user = User::factory()->create();
        $this->createTransaction($user, '2024-01-01 10:00:00', Transaction::TYPE_DEPOSIT, 20);
        $this->createTransaction($user, '2024-01-02 10:00:00', Transaction::TYPE_TRANSACTION, 5);
        $this->createTransaction($user, '2024-01-03 10:00:00', Transaction::TYPE_PAYMENT, 3);
        $deleted = $this->createTransaction($user, '2024-01-03 12:00:00', Transaction::TYPE_DEPOSIT, 100);
        $deleted->delete();

        $this->assertSame([
            ['2024-01-01', 20.0],
            ['2024-01-02', 15.0],
            ['2024-01-03', 12.0],
        ], $user->getBalanceChart('2023-12-01', '2024-01-03'));
    }

    public function test_user_balance_chart_uses_balance_before_start_date()
    {
        $user = User::factory()->create();
        $this->createTransaction($user, '2024-01-01 10:00:00', Transaction::TYPE_DEPOSIT, 10);
        $this->createTransaction($user, '2024-01-03 10:00:00', Transaction::TYPE_TRANSACTION, 4);

        $this->assertSame([
            ['2024-01-03', 6.0],
        ], $user->getBalanceChart('2024-01-03', '2024-01-03'));
    }

    public function test_product_amount_chart_tracks_inventory_and_transaction_changes()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $this->createInventory($user, $product, '2024-01-01 09:00:00', 10);
        $transaction = $this->createTransaction($user, '2024-01-02 10:00:00', Transaction::TYPE_TRANSACTION, 3);
        $transaction->products()->attach($product, ['price' => 1, 'amount' => 3]);
        $deletedInventory = $this->createInventory($user, $product, '2024-01-03 10:00:00', 50);
        $deletedInventory->delete();

        $this->assertSame([
            ['2024-01-01', 10],
            ['2024-01-02', 7],
            ['2024-01-03', 7],
        ], $product->getAmountChart('2023-12-01', '2024-01-03'));
    }

    public function test_product_amount_chart_uses_amount_before_start_date()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $this->createInventory($user, $product, '2024-01-01 09:00:00', 10);
        $transaction = $this->createTransaction($user, '2024-01-03 10:00:00', Transaction::TYPE_TRANSACTION, 4);
        $transaction->products()->attach($product, ['price' => 1, 'amount' => 4]);

        $this->assertSame([
            ['2024-01-03', 6],
        ], $product->getAmountChart('2024-01-03', '2024-01-03'));
    }
}
