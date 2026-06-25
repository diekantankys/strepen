<?php

namespace Tests\Feature\Admin;

use App\Http\Livewire\Admin\ApiKeys\Crud as ApiKeysCrud;
use App\Http\Livewire\Admin\ApiKeys\Item as ApiKeysItem;
use App\Http\Livewire\Admin\Inventories\Crud as InventoriesCrud;
use App\Http\Livewire\Admin\Posts\Crud as PostsCrud;
use App\Http\Livewire\Admin\Posts\Item as PostsItem;
use App\Http\Livewire\Admin\Products\Crud as ProductsCrud;
use App\Http\Livewire\Admin\Products\Item as ProductsItem;
use App\Http\Livewire\Admin\Transactions\Crud as TransactionsCrud;
use App\Http\Livewire\Admin\Users\Crud as UsersCrud;
use App\Http\Livewire\Admin\Users\Item as UsersItem;
use App\Models\ApiKey;
use App\Models\Inventory;
use App\Models\Post;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    public function test_admin_can_create_user_with_uploaded_assets()
    {
        Storage::fake();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(UsersCrud::class)
            ->set('user.firstname', 'New')
            ->set('user.lastname', 'Member')
            ->set('user.email', 'new.member@example.com')
            ->set('user._password', 'secret123')
            ->set('user.password_confirmation', 'secret123')
            ->set('user.role', User::ROLE_NORMAL)
            ->set('user.language', User::LANGUAGE_DUTCH)
            ->set('user.theme', User::THEME_SYSTEM)
            ->set('avatar', UploadedFile::fake()->image('avatar.jpg'))
            ->set('thanks', UploadedFile::fake()->image('thanks.gif'))
            ->call('createUser')
            ->assertHasNoErrors();

        $user = User::where('email', 'new.member@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertNotNull($user->avatar);
        $this->assertNotNull($user->thanks);
    }

    public function test_manager_cannot_change_admin_role_or_delete_admin_user()
    {
        $manager = User::factory()->manager()->create();
        $admin = User::factory()->admin()->create();
        $this->actingAs($manager);

        Livewire::test(UsersItem::class, ['user' => $admin])
            ->set('user.role', User::ROLE_NORMAL)
            ->call('editUser')
            ->assertHasNoErrors();

        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);

        Livewire::test(UsersItem::class, ['user' => $admin])
            ->call('deleteUser');

        $this->assertFalse($admin->fresh()->trashed());
    }

    public function test_product_can_be_created_edited_and_soft_deleted()
    {
        Storage::fake();
        $manager = User::factory()->manager()->create();
        $this->actingAs($manager);

        Livewire::test(ProductsCrud::class)
            ->set('product.name', 'Club Mate')
            ->set('product.price', 1.75)
            ->set('product.description', 'Caffeinated drink')
            ->set('product.alcoholic', false)
            ->set('image', UploadedFile::fake()->image('product.png'))
            ->call('createProduct')
            ->assertHasNoErrors();

        $product = Product::where('name', 'Club Mate')->first();
        $this->assertNotNull($product);
        $this->assertNotNull($product->image);

        Livewire::test(ProductsItem::class, ['product' => $product])
            ->set('product.name', 'Mate')
            ->set('product.price', 2.0)
            ->call('editProduct')
            ->assertHasNoErrors();

        $this->assertSame('Mate', $product->fresh()->name);
        $this->assertSame(2.0, $product->fresh()->price);

        Livewire::test(ProductsItem::class, ['product' => $product->fresh()])
            ->call('deleteProduct');

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_post_can_be_created_edited_and_soft_deleted()
    {
        Storage::fake();
        $manager = User::factory()->manager()->create();
        $this->actingAs($manager);

        Livewire::test(PostsCrud::class)
            ->set('post.title', 'New announcement')
            ->set('post.body', 'Body text')
            ->set('sendNotification', false)
            ->set('image', UploadedFile::fake()->image('post.jpg'))
            ->call('createPost')
            ->assertHasNoErrors();

        $post = Post::where('title', 'New announcement')->first();
        $this->assertSame($manager->id, $post->user_id);
        $this->assertNotNull($post->image);

        Livewire::test(PostsItem::class, ['post' => $post])
            ->set('post.title', 'Edited announcement')
            ->set('post.body', 'Edited body')
            ->call('editPost')
            ->assertHasNoErrors();

        $this->assertSame('Edited announcement', $post->fresh()->title);

        Livewire::test(PostsItem::class, ['post' => $post->fresh()])
            ->call('deletePost');

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_inventory_creation_updates_product_amount()
    {
        $manager = User::factory()->manager()->create();
        $product = Product::factory()->create(['amount' => 0]);
        $this->actingAs($manager);

        Livewire::test(InventoriesCrud::class)
            ->set('inventory.name', 'Restock')
            ->dispatch('inputValue', 'products', [
                ['product_id' => $product->id, 'price' => 1.25, 'amount' => 8],
            ])
            ->call('createInventory')
            ->assertHasNoErrors();

        $inventory = Inventory::where('name', 'Restock')->first();
        $this->assertNotNull($inventory);
        $this->assertSame(10.0, $inventory->price);
        $this->assertSame(8, $product->fresh()->amount);
        $this->assertDatabaseHas('inventory_product', [
            'inventory_id' => $inventory->id,
            'product_id' => $product->id,
            'price' => 1.25,
            'amount' => 8,
        ]);
    }

    public function test_admin_transaction_creation_updates_product_amount_and_balance()
    {
        $manager = User::factory()->manager()->create();
        $user = User::factory()->create(['balance' => 20]);
        $product = Product::factory()->create(['amount' => 10]);
        $this->actingAs($manager);

        Livewire::test(TransactionsCrud::class)
            ->set('transaction.user_id', $user->id)
            ->set('transaction.name', 'Admin order')
            ->dispatch('inputValue', 'products', [
                ['product_id' => $product->id, 'price' => 2.5, 'amount' => 2],
            ])
            ->call('createTransaction')
            ->assertHasNoErrors();

        $transaction = Transaction::where('name', 'Admin order')->first();
        $this->assertSame(5.0, $transaction->price);
        $this->assertSame(8, $product->fresh()->amount);
        $this->assertSame(15.0, $user->fresh()->balance);
    }

    public function test_api_key_can_be_created_edited_and_deleted()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ApiKeysCrud::class)
            ->set('apiKey.name', 'Mobile app')
            ->call('createApiKey')
            ->assertHasNoErrors();

        $apiKey = ApiKey::where('name', 'Mobile app')->first();
        $this->assertNotNull($apiKey);
        $this->assertNotEmpty($apiKey->key);

        Livewire::test(ApiKeysItem::class, ['apiKey' => $apiKey])
            ->set('apiKey.name', 'Updated app')
            ->set('apiKey.active', false)
            ->call('editApiKey')
            ->assertHasNoErrors();

        $this->assertSame('Updated app', $apiKey->fresh()->name);
        $this->assertFalse($apiKey->fresh()->active);

        Livewire::test(ApiKeysItem::class, ['apiKey' => $apiKey->fresh()])
            ->call('deleteApiKey');

        $this->assertSoftDeleted('api_keys', ['id' => $apiKey->id]);
    }
}
