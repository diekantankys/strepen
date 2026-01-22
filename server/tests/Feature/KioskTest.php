<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Tests\TestCase;

class KioskTest extends TestCase
{
    // Test to go to kiosk page when ip whitelisted
    public function test_open_kiosk_ip_on_whitelist()
    {
        Setting::set('kiosk_ip_whitelist', '127.0.0.1');
        $this->get(route('admin.kiosk'))->assertRedirect(route('transactions.create'));
    }

    // Test to go to kiosk page when ip not whitelisted
    public function test_open_kiosk_ip_not_on_whitelist()
    {
        Setting::set('kiosk_ip_whitelist', '');
        $this->get(route('admin.kiosk'))->assertStatus(403);
    }

    // Test to go to kiosk page wrong url whitelisted
    public function test_open_kiosk_direct_on_whitelist()
    {
        Setting::set('kiosk_ip_whitelist', '127.0.0.1');
        $this->get(route('transactions.create'))->assertRedirect(route('auth.login'));
    }

    // Test to go to kiosk page wrong url not whitelisted
    public function test_open_kiosk_direct_not_on_whitelist()
    {
        Setting::set('kiosk_ip_whitelist', '');
        $this->get(route('transactions.create'))->assertRedirect(route('auth.login'));
    }

    // Test to go to kiosk page when authed normal
    public function test_open_kiosk_ip_authed_normal()
    {
        Setting::set('kiosk_ip_whitelist', '');

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('admin.kiosk'))->assertStatus(403);
    }

    // Test to go to kiosk page when authed manager
    public function test_open_kiosk_ip_authed_manager()
    {
        Setting::set('kiosk_ip_whitelist', '');

        $user = User::factory()->manager()->create();
        $this->actingAs($user);

        $this->get(route('admin.kiosk'))->assertRedirect(route('transactions.create'));
    }

    // Test to go to kiosk page when authed admin
    public function test_open_kiosk_ip_authed_admin()
    {
        Setting::set('kiosk_ip_whitelist', '');

        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $this->get(route('admin.kiosk'))->assertRedirect(route('transactions.create'));
    }

    // Test kiosk / stripe page
    public function test_kiosk()
    {
        $this->actingAs(User::find(1));

        $this->get(route('transactions.create'))->assertStatus(200);
    }
}
