<?php

namespace Tests\Feature\Components;

use App\Http\Livewire\Components\ProductsChooser;
use App\Models\Product;
use App\Models\Setting;
use Livewire;
use Tests\TestCase;

class ProductsChooserTest extends TestCase
{
    public function test_products_chooser_adds_deletes_validates_and_clears_products()
    {
        $product = Product::factory()->create(['name' => 'Apple Juice', 'price' => 1.5]);

        Livewire::test(ProductsChooser::class, ['name' => 'products', 'sortBy' => 'name'])
            ->assertSeeHtml('wire:model.live.debounce.150ms="productName"')
            ->assertSeeHtml('data-chooser-type="products"')
            ->set('productName', 'Apple')
            ->assertSee('Apple')
            ->call('addFirstProduct')
            ->assertSeeHtml('wire:model.live.debounce.150ms="selectedProducts.0.amount"')
            ->assertSet('selectedProducts.0.product_id', $product->id)
            ->assertSet('selectedProducts.0.price', 1.5)
            ->assertSet('selectedProducts.0.amount', 0)
            ->assertDispatched('inputValue', 'products', [])
            ->call('inputValidate', 'products')
            ->assertSet('valid', false)
            ->set('selectedProducts.0.amount', 2)
            ->assertDispatched('inputValue', 'products', [
                ['product_id' => $product->id, 'price' => 1.5, 'amount' => 2],
            ])
            ->call('inputValidate', 'products')
            ->assertSet('valid', true)
            ->call('deleteProduct', $product->id)
            ->assertSet('selectedProducts', collect())
            ->call('inputClear', 'products')
            ->assertSet('productName', '');
    }

    public function test_products_chooser_search_field_works_after_a_product_is_selected()
    {
        $apple = Product::factory()->create(['name' => 'Apple Juice', 'active' => true]);
        $water = Product::factory()->create(['name' => 'Water', 'active' => true]);

        Livewire::test(ProductsChooser::class, ['name' => 'products', 'sortBy' => 'name'])
            ->set('productName', 'Apple')
            ->call('addFirstProduct')
            ->assertSet('selectedProducts.0.product_id', $apple->id)
            ->set('productName', 'Water')
            ->assertCount('filteredProducts', 1)
            ->assertSet('filteredProducts.1.id', $water->id)
            ->assertSee('Water')
            ->call('addFirstProduct')
            ->assertSet('selectedProducts.1.product_id', $water->id);
    }

    public function test_products_chooser_increment_decrement_and_max_amount_rules()
    {
        Setting::set('max_stripe_amount', 2);
        $product = Product::factory()->create(['price' => 2]);

        Livewire::test(ProductsChooser::class, ['name' => 'products'])
            ->call('incrementProductAmount', $product->id)
            ->call('incrementProductAmount', $product->id)
            ->call('incrementProductAmount', $product->id)
            ->assertSet('selectedProducts.0.amount', 2)
            ->call('decrementProductAmount', $product->id)
            ->call('decrementProductAmount', $product->id)
            ->call('decrementProductAmount', $product->id)
            ->assertSet('selectedProducts.0.amount', 0);

        Livewire::test(ProductsChooser::class, ['name' => 'products', 'noMax' => true])
            ->call('incrementProductAmount', $product->id)
            ->call('incrementProductAmount', $product->id)
            ->call('incrementProductAmount', $product->id)
            ->assertSet('selectedProducts.0.amount', 3);
    }

    public function test_products_chooser_minor_mode_filters_alcoholic_products()
    {
        $soda = Product::factory()->create(['name' => 'Soda', 'alcoholic' => false]);
        $beer = Product::factory()->alcoholic()->create(['name' => 'Beer']);

        Livewire::test(ProductsChooser::class, ['name' => 'products', 'minor' => true, 'sortBy' => 'name'])
            ->assertSee($soda->name)
            ->assertDontSee($beer->name);

        Livewire::test(ProductsChooser::class, ['name' => 'products', 'sortBy' => 'name'])
            ->call('incrementProductAmount', $beer->id)
            ->call('inputProps', 'products', ['minor' => true])
            ->assertSet('selectedProducts', collect())
            ->assertDispatched('inputValue', 'products', []);
    }

    public function test_products_chooser_big_mode_keeps_all_filtered_products_visible()
    {
        Product::factory()->count(12)->create(['active' => true, 'alcoholic' => false]);

        Livewire::test(ProductsChooser::class, ['name' => 'products', 'bigMode' => true])
            ->assertCount('filteredProducts', 12);
    }
}
