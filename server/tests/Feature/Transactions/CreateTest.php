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
            ->dispatch('inputValue', 'products', [
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
            ->dispatch('inputValue', 'products', [
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

    public function test_created_transaction_cannot_be_submitted_twice_and_closing_resets_form_state()
    {
        $user = User::factory()->create(['balance' => 20]);
        $this->actingAs($user);

        $product = Product::factory()->create(['price' => 2.5, 'amount' => 10]);

        Livewire::test(Create::class)
            ->dispatch('inputValue', 'products', [
                [
                    'product_id' => $product->id,
                    'amount' => 2,
                ],
            ])
            ->call('createTransaction')
            ->assertSet('isCreated', true)
            ->call('createTransaction')
            ->call('closeCreated')
            ->assertSet('isCreated', false)
            ->assertSet('selectedProducts', [])
            ->assertSet('invalidInputs', [])
            ->call('createTransaction')
            ->assertSet('invalidInputs', ['products']);

        $this->assertSame(1, Transaction::where('user_id', $user->id)->count());
        $this->assertSame(5.0, $user->fresh()->transactions()->first()->price);
        $this->assertSame(15.0, $user->fresh()->balance);
        $this->assertSame(8, $product->fresh()->amount);
    }
}
