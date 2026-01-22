<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Create system / kiosk account (must by user_id=1!!!)
        $user = new User;
        $user->firstname = config('app.name');
        $user->lastname = 'System';
        $user->email = 'system@'.strtolower(config('app.name'));
        $user->password = Hash::make(Str::random(32));
        $user->active = false;
        $user->receive_news = false;
        $user->save();

        // Create admin account
        $user = new User;
        $user->firstname = 'Admin';
        $user->lastname = 'Admin';
        $user->email = 'code@diekantankys.nl';
        $user->password = Hash::make('admin123');
        $user->role = User::ROLE_ADMIN;
        $user->save();

        // Create website api key
        $apiKey = new ApiKey;
        $apiKey->name = 'Website';
        $apiKey->key = ApiKey::generateKey();
        $apiKey->save();

        // Create settings key values pares
        $setting = new Setting;
        $setting->key = 'currency_symbol';
        $setting->value = '€';
        $setting->save();

        $setting = new Setting;
        $setting->key = 'currency_name';
        $setting->value = 'euro';
        $setting->save();

        $setting = new Setting;
        $setting->key = 'min_user_balance';
        $setting->value = 20;
        $setting->save();

        $setting = new Setting;
        $setting->key = 'max_stripe_amount';
        $setting->value = 24;
        $setting->save();

        $setting = new Setting;
        $setting->key = 'minor_age';
        $setting->value = 18;
        $setting->save();

        $setting = new Setting;
        $setting->key = 'pagination_rows';
        $setting->value = '4';
        $setting->save();

        $setting = new Setting;
        $setting->key = 'kiosk_ip_whitelist';
        $setting->value = '127.0.0.1, 88.159.13.135';
        $setting->save();

        $setting = new Setting;
        $setting->key = 'leaderboards_enabled';
        $setting->value = 'true';
        $setting->save();

        $setting = new Setting;
        $setting->key = 'bank_account_iban';
        $setting->value = '?';
        $setting->save();

        $setting = new Setting;
        $setting->key = 'bank_account_holder';
        $setting->value = '?';
        $setting->save();

        $setting = new Setting;
        $setting->key = 'product_beer_ids';
        $setting->value = '';
        $setting->save();

        $setting = new Setting;
        $setting->key = 'product_soda_ids';
        $setting->value = '';
        $setting->save();

        $setting = new Setting;
        $setting->key = 'product_snack_ids';
        $setting->value = '';
        $setting->save();

        $setting = new Setting;
        $setting->key = 'default_user_avatar';
        $setting->value = 'default.png';
        $setting->save();

        $setting = new Setting;
        $setting->key = 'default_user_thanks';
        $setting->value = 'default.gif';
        $setting->save();

        $setting = new Setting;
        $setting->key = 'default_product_image';
        $setting->value = 'default.png';
        $setting->save();

        // Create 50 random users
        // User::factory(50)->create();
    }
}
