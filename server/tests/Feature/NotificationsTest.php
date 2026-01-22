<?php

namespace Tests\Feature;

use App\Http\LiveWire\Notifications;
use App\Models\Transaction;
use App\Models\User;
use Livewire;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    // Test notifications page guest
    public function test_notifications_guest()
    {
        $this->get(route('notifications'))->assertRedirect(route('auth.login'));
    }

    // Test notifications see notification
    public function test_notifications_see_notification()
    {
        $user = User::factory()
            ->has(Transaction::factory()->count(25))
            ->create();
        $this->actingAs($user);

        $user->recalculateBalance();
        $user->update();
        User::checkBalances();

        Livewire::test(Notifications::class)
            ->assertSee('Balans te laag');
    }

    // Test notifications component read notification
    public function test_notifications_read_notification()
    {
        $user = User::factory()
            ->has(Transaction::factory()->count(25))
            ->create();
        $this->actingAs($user);

        $user->recalculateBalance();
        $user->update();
        User::checkBalances();

        $notificationId = $user->unreadNotifications->first()->id;
        Livewire::test(Notifications::class)
            ->call('readNotification', $notificationId)
            ->assertDontSee('readNotification(\''.$notificationId.'\')');
    }

    // Test notifications component read notification from other
    public function test_notifications_read_notification_from_other()
    {
        $user = User::factory()
            ->has(Transaction::factory()->count(25))
            ->create();
        $user->recalculateBalance();
        $user->update();
        User::checkBalances();

        $user2 = User::factory()->create();
        $this->actingAs($user2);

        $notificationId = $user->unreadNotifications->first()->id;
        Livewire::test(Notifications::class)
            ->call('readNotification', $notificationId)
            ->assertDontSee($notificationId);

        $this->actingAs($user);
        Livewire::test(Notifications::class)
            ->assertSee($notificationId);
    }
}
