<?php

namespace Tests\Feature\ViewComponents;

use App\Models\Product;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\View\Components\ProductsAmounts;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ViewComponentsTest extends TestCase
{
    public function test_money_amount_change_and_medal_components_render_expected_text()
    {
        Setting::set('currency_symbol', '€');
        App::setLocale('nl');

        $money = Blade::render('<x-money-format :money="-12.5" />');
        $this->assertStringContainsString('€ -12,50', preg_replace('/\s+/', ' ', $money));
        $this->assertStringContainsString('has-text-danger', $money);

        $amount = Blade::render('<x-amount-format :amount="1200" :isPerHour="true" />');
        $this->assertStringContainsString('1.200/h', preg_replace('/\s+/', '', $amount));

        $change = Blade::render('<x-change-format :change="5" :isMoney="true" />');
        $this->assertStringContainsString('has-text-success', $change);
        $this->assertStringContainsString('€ 5,00', preg_replace('/\s+/', ' ', $change));

        $this->assertStringContainsString('is-gold', Blade::render('<x-index-medal :index="0" />'));
        $this->assertStringContainsString('<div class="medal">4</div>', preg_replace('/\s+/', ' ', Blade::render('<x-index-medal :index="3" />')));
    }

    public function test_products_amounts_component_fills_missing_pivot_prices_when_total_matches()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 2.5]);
        $transaction = Transaction::factory()->for($user)->create(['price' => 5]);
        $transaction->products()->attach($product, ['price' => null, 'amount' => 2]);
        $products = $transaction->products()->get();

        new ProductsAmounts($products, 5);

        $this->assertSame(2.5, $products->first()->pivot->price);
    }

    public function test_transaction_type_and_search_header_components_render_controls()
    {
        $this->assertStringContainsString('wire:model.defer="type"', Blade::render('<x-transaction-type-chooser />'));

        $search = Blade::render('<x-search-header itemName="products"><span>Filters</span></x-search-header>');
        $this->assertStringContainsString('wire:submit.prevent="search"', $search);
        $this->assertStringContainsString('Filters', $search);
    }
}
