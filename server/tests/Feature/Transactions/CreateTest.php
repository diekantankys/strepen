<?php

namespace Tests\Feature\Transactions;

use App\Http\Livewire\Transactions\Create;
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
        $user = User::factory()->create(['balance' => 20]);
        $this->actingAs($user);

        $product = Product::factory()->create(['price' => 2.5, 'amount' => 10]);
        $amount = 2;
        Livewire::test(Create::class)
            ->emit('inputValue', 'products', [
                [
                    'product_id' => $product->id,
                    'amount' => $amount,
                ],
            ])
            ->call('createTransaction');

        $transaction = $user->transactions()->first();
        $this->assertNotNull($transaction);
        $this->assertSame(2.5 * $amount, $transaction->price);
        $this->assertSame(20 - (2.5 * $amount), $user->fresh()->balance);
        $this->assertSame(10 - $amount, $product->fresh()->amount);
        $this->assertDatabaseHas('transaction_product', [
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'price' => 2.5,
            'amount' => $amount,
        ]);
    }
}
