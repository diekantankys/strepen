<?php

namespace Tests\Feature\Components;

use App\Http\LiveWire\Components\Notifications;
use App\Models\Transaction;
use App\Models\User;
use Livewire;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    // Test notifications component exists
    public function test_notifications_exists()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('home'))->assertSeeLivewire('components.notifications');
    }

    // Test notifications component see notification
    public function test_notifications_see_notification()
    {
        $user = User::factory()
            ->has(Transaction::factory()->count(25))
            ->create();
        $this->actingAs($user);

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
            ->assertDontSee($notificationId);
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
