<?php

namespace Tests\Feature\Transactions;

use App\Http\LiveWire\Transactions\History;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    // Test transactions history page guest
    public function test_transaction_history_guest()
    {
        $this->get(route('transactions.history'))->assertRedirect(route('auth.login'));
    }

    // Test transactions history page to see transactions
    public function test_see_transactions()
    {
        $user = User::factory()
            ->has(Transaction::factory()->count(5))
            ->create();
        $this->actingAs($user);

        Livewire::test(History::class)
            ->assertSee($user->transactions()->orderBy('created_at')->first()->name);
    }

    // Test transactions history page to search transactions
    public function test_search_transactions()
    {
        $user = User::factory()
            ->has(Transaction::factory()->count(5))
            ->create();
        $this->actingAs($user);

        $searchTransaction = $user->transactions->random();
        Livewire::test(History::class)
            ->set('query', Str::substr($searchTransaction->name, 0, 8))
            ->call('search')
            ->assertSee($searchTransaction->name);
    }
}
