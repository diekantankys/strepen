<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    // Test to logout when guest
    public function test_logout_guest()
    {
        $this->post(route('auth.logout'))->assertRedirect(route('auth.login'));
    }

    // Test to logout when authed
    public function test_logout_authed()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('auth.logout'))->assertRedirect(route('auth.login'));
    }
}
