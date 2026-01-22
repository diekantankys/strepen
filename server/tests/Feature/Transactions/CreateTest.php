<?php

namespace Tests\Feature\Transactions;

use App\Http\LiveWire\Transactions\Create;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Livewire;
use Tests\TestCase;

class CreateTest extends TestCase
{
    // Test transactions create page guest
    public function test_transaction_create_guest()
    {
        $this->get(route('transactions.create'))->assertRedirect(route('auth.login'));
    }

    // Test transactions create transaction with no products
    public function test_create_transaction_with_no_products()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Create::class)
            ->call('createTransaction');

        $this->assertTrue($user->transactions->count() == 0);
    }

    // Test transactions create transaction with product no amount
    public function test_create_transaction_with_product_no_amount()
    {
        $product = Product::factory()->create();

        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Create::class)
            ->emit('inputValue', 'products', [
                [
                    'product_id' => $product->id,
                    'amount' => 0,
                ],
            ])
            ->call('createTransaction');

        $this->assertTrue($user->transactions->count() == 0);
    }

    // Test transactions create transaction with product with amount
    public function test_create_transaction_with_product_with_amount()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create();
        Livewire::test(Create::class)
            ->emit('inputValue', 'products', [
                [
                    'product_id' => $product->id,
                    'amount' => $this->faker->numberBetween(1, 5),
                ],
            ])
            ->call('createTransaction');

        $this->assertTrue($user->transactions->count() == 1);
    }
}
