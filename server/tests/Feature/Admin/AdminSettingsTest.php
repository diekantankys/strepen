<?php

namespace Tests\Feature\Admin;

use App\Http\Livewire\Admin\Settings\ChangeDefaultAvatar;
use App\Http\Livewire\Admin\Settings\ChangeDefaultProductImage;
use App\Http\Livewire\Admin\Settings\ChangeDefaultThanks;
use App\Http\Livewire\Admin\Settings\ChangeSettings;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    public function test_admin_can_change_general_settings_and_product_category_ids()
    {
        $admin = User::factory()->admin()->create();
        $beer = Product::factory()->create();
        $soda = Product::factory()->create();
        $snack = Product::factory()->create();
        $this->actingAs($admin);

        Livewire::test(ChangeSettings::class)
            ->set('currencySymbol', '$')
            ->set('currencyName', 'dollar')
            ->set('minUserBalance', 12.5)
            ->set('maxStripeAmount', 18)
            ->set('minorAge', 21)
            ->set('paginationRows', 8)
            ->set('kioskIpWhitelist', '127.0.0.1')
            ->set('leaderboardsEnabled', false)
            ->set('bankAccountIban', 'NL00TEST0123456789')
            ->set('bankAccountHolder', 'Test Holder')
            ->emit('inputValue', 'product_beer', $beer->id)
            ->emit('inputValue', 'product_soda', $soda->id)
            ->emit('inputValue', 'product_snack', $snack->id)
            ->call('removeProductId', 'soda', $soda->id)
            ->call('changeDetails')
            ->assertHasNoErrors()
            ->assertSet('isChanged', true);

        $this->assertSame('$', Setting::get('currency_symbol'));
        $this->assertSame('dollar', Setting::get('currency_name'));
        $this->assertSame(12.5, (float) Setting::get('min_user_balance'));
        $this->assertSame('false', Setting::get('leaderboards_enabled'));
        $this->assertSame((string) $beer->id, Setting::get('product_beer_ids'));
        $this->assertSame('', Setting::get('product_soda_ids'));
        $this->assertSame((string) $snack->id, Setting::get('product_snack_ids'));
    }

    public function test_default_asset_settings_accept_valid_files_and_can_reset_to_defaults()
    {
        Storage::fake();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ChangeDefaultAvatar::class)
            ->set('avatar', UploadedFile::fake()->image('avatar.png'))
            ->call('changeAvatar')
            ->assertHasNoErrors()
            ->assertSet('isChanged', true);

        $this->assertNotSame('default.png', Setting::get('default_user_avatar'));

        Livewire::test(ChangeDefaultAvatar::class)
            ->call('deleteAvatar')
            ->assertSet('isDeleted', true);
        $this->assertSame('default.png', Setting::get('default_user_avatar'));

        Livewire::test(ChangeDefaultThanks::class)
            ->set('thanks', UploadedFile::fake()->image('thanks.gif'))
            ->call('changeThanks')
            ->assertHasNoErrors()
            ->assertSet('isChanged', true);
        $this->assertNotSame('default.gif', Setting::get('default_user_thanks'));

        Livewire::test(ChangeDefaultThanks::class)
            ->call('deleteThanks')
            ->assertSet('isDeleted', true);
        $this->assertSame('default.gif', Setting::get('default_user_thanks'));

        Livewire::test(ChangeDefaultProductImage::class)
            ->set('image', UploadedFile::fake()->image('product.jpg'))
            ->call('changeImage')
            ->assertHasNoErrors()
            ->assertSet('isChanged', true);
        $this->assertNotSame('default.png', Setting::get('default_product_image'));

        Livewire::test(ChangeDefaultProductImage::class)
            ->call('deleteImage')
            ->assertSet('isDeleted', true);
        $this->assertSame('default.png', Setting::get('default_product_image'));
    }
}
