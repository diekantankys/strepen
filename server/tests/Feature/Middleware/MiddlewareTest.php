<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    public function test_language_middleware_sets_english_locale_for_english_users()
    {
        Route::middleware('web')->get('/_test_locale', fn () => App::getLocale());

        $englishUser = User::factory()->create(['language' => User::LANGUAGE_ENGLISH]);
        $this->actingAs($englishUser)->get('/_test_locale')->assertOk()->assertSee('en');

        App::setLocale(config('app.locale'));
        $dutchUser = User::factory()->create(['language' => User::LANGUAGE_DUTCH]);
        $this->actingAs($dutchUser)->get('/_test_locale')->assertOk()->assertSee(config('app.locale'));
    }

    public function test_no_kiosk_middleware_redirects_system_user_from_no_kiosk_pages()
    {
        $this->actingAs(User::find(1))
            ->get(route('settings'))
            ->assertRedirect('transactions.create');
    }

    public function test_auth_and_guest_route_redirects()
    {
        $user = User::factory()->create();

        foreach ([route('settings'), route('balance'), route('notifications'), route('transactions.history')] as $route) {
            $this->get($route)->assertRedirect(route('auth.login'));
        }

        $this->actingAs($user);
        foreach ([route('settings'), route('balance'), route('notifications'), route('transactions.history')] as $route) {
            $this->get($route)->assertOk();
        }
        $this->actingAs($user)->get(route('auth.login'))->assertRedirect(route('home'));
    }
}
