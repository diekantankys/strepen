<?php

namespace Tests\Feature\Admin;

use App\Http\Livewire\Admin\Inventories\Item as InventoryItem;
use App\Http\Livewire\Admin\PostComments\Item as PostCommentItem;
use App\Http\Livewire\Admin\Posts\Item as PostItem;
use App\Http\Livewire\Admin\Products\Item as ProductItem;
use App\Http\Livewire\Admin\Transactions\Item as TransactionItem;
use App\Http\Livewire\Admin\Users\Item as UserItem;
use App\Models\Inventory;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire;
use Tests\TestCase;

class AdminItemBehaviorTest extends TestCase
{
    private function inventory(User $user, Product $product, int $amount = 5, float $price = 2): Inventory
    {
        $inventory = new Inventory;
        $inventory->user_id = $user->id;
        $inventory->name = 'Initial inventory';
        $inventory->price = $amount * $price;
        $inventory->save();
        $inventory->products()->attach($product, ['price' => $price, 'amount' => $amount]);
        $product->recalculateAmount();
        $product->save();

        return $inventory;
    }

    public function test_inventory_item_edit_and_delete_recalculate_product_amounts()
    {
        $manager = User::factory()->manager()->create();
        $product = Product::factory()->create(['amount' => 0]);
        $inventory = $this->inventory($manager, $product);
        $this->actingAs($manager);

        Livewire::test(InventoryItem::class, ['inventory' => $inventory])
            ->dispatch('inputValue', 'item_products', [
                ['product_id' => $product->id, 'price' => 3.0, 'amount' => 7],
            ])
            ->set('inventory.name', 'Edited inventory')
            ->set('createdAtDate', '2024-02-03')
            ->set('createdAtTime', '04:05:06')
            ->call('editInventory')
            ->assertHasNoErrors();

        $inventory = $inventory->fresh();
        $this->assertSame('Edited inventory', $inventory->name);
        $this->assertSame(21.0, $inventory->price);
        $this->assertSame(7, $product->fresh()->amount);
        $this->assertDatabaseHas('inventory_product', [
            'inventory_id' => $inventory->id,
            'product_id' => $product->id,
            'price' => 3.0,
            'amount' => 7,
        ]);

        Livewire::test(InventoryItem::class, ['inventory' => $inventory])
            ->call('deleteInventory');

        $this->assertSoftDeleted('inventories', ['id' => $inventory->id]);
        $this->assertSame(0, $product->fresh()->amount);
    }

    public function test_transaction_item_edit_moves_balance_between_users_and_delete_restores_amount()
    {
        $manager = User::factory()->manager()->create();
        $oldUser = User::factory()->create();
        $newUser = User::factory()->create();
        $product = Product::factory()->create(['amount' => 10]);
        $this->inventory($manager, $product, 10, 1);
        $transaction = Transaction::factory()->for($oldUser)->create([
            'name' => 'Original transaction',
            'price' => 4,
        ]);
        $transaction->products()->attach($product, ['price' => 2, 'amount' => 2]);
        $product->recalculateAmount();
        $product->save();
        $oldUser->recalculateBalance();
        $oldUser->save();
        $this->actingAs($manager);

        Livewire::test(TransactionItem::class, ['transaction' => $transaction])
            ->dispatch('inputValue', 'item_user', $newUser->id)
            ->dispatch('inputValue', 'item_products', [
                ['product_id' => $product->id, 'price' => 3.0, 'amount' => 3],
            ])
            ->set('transaction.name', 'Edited transaction')
            ->call('editTransaction')
            ->assertHasNoErrors();

        $transaction = $transaction->fresh();
        $this->assertSame('Edited transaction', $transaction->name);
        $this->assertSame(9.0, $transaction->price);
        $this->assertSame(0.0, $oldUser->fresh()->balance);
        $this->assertSame(-9.0, $newUser->fresh()->balance);
        $this->assertSame(7, $product->fresh()->amount);

        Livewire::test(TransactionItem::class, ['transaction' => $transaction])
            ->call('deleteTransaction');

        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
        $this->assertSame(0.0, $newUser->fresh()->balance);
        $this->assertSame(10, $product->fresh()->amount);
    }

    public function test_transaction_item_edits_deposit_and_payment_balances()
    {
        $manager = User::factory()->manager()->create();
        $user = User::factory()->create();
        $deposit = Transaction::factory()->for($user)->create([
            'type' => Transaction::TYPE_DEPOSIT,
            'price' => 5,
        ]);
        $this->actingAs($manager);

        Livewire::test(TransactionItem::class, ['transaction' => $deposit])
            ->set('transaction.price', 12)
            ->call('editTransaction')
            ->assertHasNoErrors();

        $this->assertSame(12.0, $user->fresh()->balance);
    }

    public function test_user_item_edits_password_deletes_assets_and_hijacks_user()
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['avatar' => 'custom.jpg', 'thanks' => 'custom.gif']);
        $this->actingAs($admin);

        Livewire::test(UserItem::class, ['user' => $user])
            ->set('newPassword', 'changed-secret')
            ->set('newPasswordConfirmation', 'changed-secret')
            ->call('editUser')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('changed-secret', $user->fresh()->password));

        Livewire::test(UserItem::class, ['user' => $user->fresh()])
            ->call('deleteAvatar')
            ->call('deleteThanks');

        $this->assertNull($user->fresh()->avatar);
        $this->assertNull($user->fresh()->thanks);

        Livewire::test(UserItem::class, ['user' => $user->fresh()])
            ->call('hijackUser')
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_product_and_post_item_delete_images_and_edit_created_at()
    {
        $manager = User::factory()->manager()->create();
        $product = Product::factory()->create(['image' => 'custom.png']);
        $post = Post::factory()->for($manager)->create(['image' => 'custom.jpg']);
        $this->actingAs($manager);

        Livewire::test(ProductItem::class, ['product' => $product])
            ->call('deleteImage');
        $this->assertNull($product->fresh()->image);

        Livewire::test(PostItem::class, ['post' => $post])
            ->set('createdAtDate', '2024-03-04')
            ->set('createdAtTime', '05:06:07')
            ->set('post.title', 'Edited post date')
            ->set('post.body', 'Edited post body')
            ->call('editPost')
            ->assertHasNoErrors();

        $this->assertSame('2024-03-04 05:06:07', $post->fresh()->created_at->format('Y-m-d H:i:s'));

        Livewire::test(PostItem::class, ['post' => $post->fresh()])
            ->call('deleteImage');
        $this->assertNull($post->fresh()->image);
    }

    public function test_post_comment_item_delete_removes_replies()
    {
        $manager = User::factory()->manager()->create();
        $post = Post::factory()->for($manager)->create();
        $parent = new PostComment;
        $parent->post_id = $post->id;
        $parent->user_id = User::factory()->create()->id;
        $parent->body = 'Parent comment';
        $parent->save();
        $reply = new PostComment;
        $reply->post_id = $post->id;
        $reply->user_id = User::factory()->create()->id;
        $reply->parent_id = $parent->id;
        $reply->body = 'Reply comment';
        $reply->save();
        $this->actingAs($manager);

        Livewire::test(PostCommentItem::class, ['comment' => $parent])
            ->call('deleteComment');

        $this->assertSoftDeleted('post_comments', ['id' => $parent->id]);
        $this->assertSoftDeleted('post_comments', ['id' => $reply->id]);
    }
}
