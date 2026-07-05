<?php

namespace Tests\Feature\Settings;

use App\Http\Livewire\Settings\ChangeNotifications;
use App\Models\User;
use Livewire;
use Tests\TestCase;

class ChangeNotificationsTest extends TestCase
{
    public function test_change_notifications_saves_all_preferences()
    {
        $user = User::factory()->create([
            'notify_new_posts' => true,
            'notify_low_balance' => true,
            'notify_new_deposits' => true,
            'notify_new_transactions' => false,
            'notify_by_email' => true,
        ]);
        $this->actingAs($user);

        Livewire::test(ChangeNotifications::class)
            ->set('user.notify_new_posts', false)
            ->set('user.notify_low_balance', false)
            ->set('user.notify_new_deposits', false)
            ->set('user.notify_new_transactions', true)
            ->set('user.notify_by_email', false)
            ->call('changeNotifications')
            ->assertHasNoErrors()
            ->assertSet('isChanged', true);

        $user = $user->fresh();
        $this->assertFalse($user->notify_new_posts);
        $this->assertFalse($user->notify_low_balance);
        $this->assertFalse($user->notify_new_deposits);
        $this->assertTrue($user->notify_new_transactions);
        $this->assertFalse($user->notify_by_email);
    }

    public function test_change_notifications_loads_current_preferences()
    {
        $user = User::factory()->create([
            'notify_new_posts' => false,
            'notify_low_balance' => true,
            'notify_new_deposits' => false,
            'notify_new_transactions' => true,
            'notify_by_email' => false,
        ]);
        $this->actingAs($user);

        Livewire::test(ChangeNotifications::class)
            ->assertSet('user.notify_new_posts', false)
            ->assertSet('user.notify_low_balance', true)
            ->assertSet('user.notify_new_deposits', false)
            ->assertSet('user.notify_new_transactions', true)
            ->assertSet('user.notify_by_email', false);
    }
}
