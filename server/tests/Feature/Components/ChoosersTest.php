<?php

namespace Tests\Feature\Components;

use App\Http\Livewire\Components\ProductChooser;
use App\Http\Livewire\Components\UserChooser;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Livewire;
use Tests\TestCase;

class ChoosersTest extends TestCase
{
    public function test_user_chooser_filters_selects_validates_and_clears_users()
    {
        $included = User::factory()->create(['firstname' => 'Alpha', 'lastname' => 'Tester']);
        $excluded = User::factory()->create(['firstname' => 'Beta', 'lastname' => 'Tester', 'active' => false]);

        Livewire::test(UserChooser::class, ['name' => 'user'])
            ->set('userName', 'Alpha')
            ->assertSee('Alpha')
            ->assertDontSee('Beta')
            ->call('selectFirstUser')
            ->assertSet('user.id', $included->id)
            ->assertDispatched('inputValue', 'user', $included->id)
            ->call('inputValidate', 'user')
            ->assertSet('valid', true)
            ->call('inputClear', 'user')
            ->assertSet('user', null)
            ->assertDispatched('inputValue', 'user', null);
    }

    public function test_user_chooser_can_search_again_after_selected_user_is_cleared_by_typing()
    {
        $alpha = User::factory()->create(['firstname' => 'Alpha', 'lastname' => 'Tester']);
        $beta = User::factory()->create(['firstname' => 'Beta', 'lastname' => 'Tester']);

        Livewire::test(UserChooser::class, ['name' => 'user'])
            ->assertSeeHtml('wire:model.live.debounce.150ms="userName"')
            ->assertSeeHtml('data-chooser-type="user"')
            ->set('userName', 'Alpha')
            ->call('selectFirstUser')
            ->assertSet('user.id', $alpha->id)
            ->set('userName', 'Beta')
            ->assertSet('user', null)
            ->assertDispatched('inputValue', 'user', null)
            ->assertSee('Beta')
            ->assertDontSee('Alpha')
            ->call('selectFirstUser')
            ->assertSet('user.id', $beta->id);
    }

    public function test_user_chooser_can_require_posts()
    {
        $withPost = User::factory()->has(Post::factory())->create(['firstname' => 'Poster']);
        User::factory()->create(['firstname' => 'NoPosts']);

        Livewire::test(UserChooser::class, ['name' => 'user', 'postsRequired' => true])
            ->assertSee($withPost->name)
            ->assertDontSee('NoPosts');
    }

    public function test_product_chooser_filters_inactive_products_unless_included()
    {
        $active = Product::factory()->create(['name' => 'Active Cola', 'active' => true]);
        $inactive = Product::factory()->create(['name' => 'Inactive Cola', 'active' => false]);

        Livewire::test(ProductChooser::class, ['name' => 'product'])
            ->set('productName', 'Cola')
            ->assertSee('Active')
            ->assertDontSee('Inactive')
            ->call('selectFirstProduct')
            ->assertSet('product.id', $active->id)
            ->assertDispatched('inputValue', 'product', $active->id);

        Livewire::test(ProductChooser::class, ['name' => 'product', 'includeInactive' => true])
            ->set('productName', 'Inactive')
            ->assertSee('Inactive');
    }

    public function test_product_chooser_can_search_again_after_selected_product_is_cleared_by_typing()
    {
        $cola = Product::factory()->create(['name' => 'Cola', 'active' => true]);
        $water = Product::factory()->create(['name' => 'Water', 'active' => true]);

        Livewire::test(ProductChooser::class, ['name' => 'product'])
            ->assertSeeHtml('wire:model.live.debounce.150ms="productName"')
            ->assertSeeHtml('data-chooser-type="product"')
            ->set('productName', 'Cola')
            ->call('selectFirstProduct')
            ->assertSet('product.id', $cola->id)
            ->set('productName', 'Water')
            ->assertSet('product', null)
            ->assertDispatched('inputValue', 'product', null)
            ->assertSee('Water')
            ->assertDontSee('Cola')
            ->call('selectFirstProduct')
            ->assertSet('product.id', $water->id);
    }
}
