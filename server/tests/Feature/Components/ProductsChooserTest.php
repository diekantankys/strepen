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
            ->set('productName', 'Apple')
            ->assertSee('Apple')
            ->call('addFirstProduct')
            ->assertSet('selectedProducts.0.product_id', $product->id)
            ->assertSet('selectedProducts.0.price', 1.5)
            ->assertSet('selectedProducts.0.amount', 0)
            ->assertEmittedUp('inputValue', 'products', [])
            ->call('inputValidate', 'products')
            ->assertSet('valid', false)
            ->set('selectedProducts.0.amount', 2)
            ->assertEmittedUp('inputValue', 'products', [
                ['product_id' => $product->id, 'price' => 1.5, 'amount' => 2],
            ])
            ->call('inputValidate', 'products')
            ->assertSet('valid', true)
            ->call('deleteProduct', $product->id)
            ->assertSet('selectedProducts', collect())
            ->call('inputClear', 'products')
            ->assertSet('productName', '');
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
            ->assertEmittedUp('inputValue', 'products', []);
    }

    public function test_products_chooser_big_mode_keeps_all_filtered_products_visible()
    {
        Product::factory()->count(12)->create(['active' => true, 'alcoholic' => false]);

        Livewire::test(ProductsChooser::class, ['name' => 'products', 'bigMode' => true])
            ->assertCount('filteredProducts', 12);
    }
}
