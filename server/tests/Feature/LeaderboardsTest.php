<?php

namespace Tests\Feature;

use App\Http\LiveWire\Leaderboards;
use App\Models\Setting;
use App\Models\User;
use Livewire;
use Tests\TestCase;

class LeaderboardsTest extends TestCase
{
    // Test leaderboards page guest
    public function test_leaderboards_guest()
    {
        $this->get(route('leaderboards'))->assertRedirect(route('auth.login'));
    }

    // Test leaderboards page when enabled
    public function test_leaderboards_enabled()
    {
        Setting::set('leaderboards_enabled', 'true');

        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Leaderboards::class)
            ->assertSee('Meeste biertjes');
    }

    // Test leaderboards page when disabled
    public function test_leaderboards_disabled()
    {
        Setting::set('leaderboards_enabled', 'false');

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('leaderboards'))->assertStatus(403);
    }
}
