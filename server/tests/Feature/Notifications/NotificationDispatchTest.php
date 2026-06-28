<?php

namespace Tests\Feature\Notifications;

use App\Http\Livewire\Admin\Posts\Crud as PostsCrud;
use App\Http\Livewire\Admin\Transactions\Crud as TransactionsCrud;
use App\Http\Livewire\Transactions\Create;
use App\Models\ApiKey;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewDeposit;
use App\Notifications\NewPost;
use App\Notifications\NewTransaction;
use Illuminate\Support\Facades\Notification;
use Livewire;
use Tests\TestCase;

class NotificationDispatchTest extends TestCase
{
    private function apiKey(): string
    {
        return ApiKey::first()->key;
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test token')->plainTextToken;
    }

    // Web (kiosk/self-service) stripe dispatches NewTransaction
    public function test_web_stripe_dispatches_new_transaction_notification()
    {
        Notification::fake();
        $user = User::factory()->create(['balance' => 20]);
        $product = Product::factory()->create(['price' => 2.5, 'amount' => 10]);
        $this->actingAs($user);

        Livewire::test(Create::class)
            ->dispatch('inputValue', 'products', [
                ['product_id' => $product->id, 'amount' => 1],
            ])
            ->call('createTransaction')
            ->assertHasNoErrors();

        Notification::assertSentTo($user, NewTransaction::class);
    }

    // API stripe does NOT dispatch NewTransaction (user is on their own phone)
    public function test_api_stripe_does_not_dispatch_new_transaction_notification()
    {
        Notification::fake();
        $user = User::factory()->create(['balance' => 20]);
        $product = Product::factory()->create(['price' => 2.5, 'amount' => 10]);

        $this->withHeader('Authorization', 'Bearer '.$this->tokenFor($user))
            ->postJson(route('api.transactions.store', ['api_key' => $this->apiKey()]), [
                'name' => 'Mobile order',
                'products' => [['product_id' => $product->id, 'amount' => 1]],
            ])->assertOk();

        Notification::assertNotSentTo($user, NewTransaction::class);
    }

    // Admin transaction with sendNotification=true dispatches NewTransaction
    public function test_admin_transaction_dispatches_notification_when_enabled()
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['balance' => 20]);
        $product = Product::factory()->create(['price' => 3.0, 'amount' => 5]);
        $this->actingAs($admin);

        Livewire::test(TransactionsCrud::class)
            ->set('transaction.user_id', $user->id)
            ->set('transaction.name', 'Admin stripe')
            ->set('sendNotification', true)
            ->dispatch('inputValue', 'products', [
                ['product_id' => $product->id, 'price' => 3.0, 'amount' => 1],
            ])
            ->call('createTransaction')
            ->assertHasNoErrors();

        Notification::assertSentTo($user, NewTransaction::class);
    }

    // Admin transaction with sendNotification=false suppresses notification
    public function test_admin_transaction_suppresses_notification_when_disabled()
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['balance' => 20]);
        $product = Product::factory()->create(['price' => 3.0, 'amount' => 5]);
        $this->actingAs($admin);

        Livewire::test(TransactionsCrud::class)
            ->set('transaction.user_id', $user->id)
            ->set('transaction.name', 'Admin silent stripe')
            ->set('sendNotification', false)
            ->dispatch('inputValue', 'products', [
                ['product_id' => $product->id, 'price' => 3.0, 'amount' => 1],
            ])
            ->call('createTransaction')
            ->assertHasNoErrors();

        Notification::assertNotSentTo($user, NewTransaction::class);
    }

    // Admin single deposit with sendNotification=true dispatches NewDeposit
    public function test_admin_single_deposit_dispatches_notification_when_enabled()
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['balance' => 0]);
        $this->actingAs($admin);

        Livewire::test(TransactionsCrud::class)
            ->set('transaction.user_id', $user->id)
            ->set('transaction.name', 'Test deposit')
            ->set('transaction.price', 10.0)
            ->set('creatingDepositTab', 'single')
            ->set('sendNotification', true)
            ->call('createDeposit')
            ->assertHasNoErrors();

        Notification::assertSentTo($user, NewDeposit::class);
    }

    // Admin single deposit with sendNotification=false suppresses notification
    public function test_admin_single_deposit_suppresses_notification_when_disabled()
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['balance' => 0]);
        $this->actingAs($admin);

        Livewire::test(TransactionsCrud::class)
            ->set('transaction.user_id', $user->id)
            ->set('transaction.name', 'Silent deposit')
            ->set('transaction.price', 10.0)
            ->set('creatingDepositTab', 'single')
            ->set('sendNotification', false)
            ->call('createDeposit')
            ->assertHasNoErrors();

        Notification::assertNotSentTo($user, NewDeposit::class);
    }

    // Admin multiple deposits notify each recipient
    public function test_admin_multiple_deposits_notify_each_recipient()
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $user1 = User::factory()->create(['firstname' => 'Aalice', 'balance' => 0]);
        $user2 = User::factory()->create(['firstname' => 'Zbob', 'balance' => 0]);
        $this->actingAs($admin);

        // Build userAmounts indexed to match how the component orders users
        $users = User::where('active', true)->orderByRaw('active DESC, LOWER(firstname)')->get();
        $amounts = array_fill(0, $users->count(), '');
        $amounts[$users->search(fn ($u) => $u->id === $user1->id)] = '5.00';
        $amounts[$users->search(fn ($u) => $u->id === $user2->id)] = '8.00';

        Livewire::test(TransactionsCrud::class)
            ->set('transaction.name', 'Bulk deposit')
            ->set('creatingDepositTab', 'multiple')
            ->set('sendNotification', true)
            ->set('userAmounts', $amounts)
            ->call('createDeposit')
            ->assertHasNoErrors();

        Notification::assertSentTo($user1, NewDeposit::class);
        Notification::assertSentTo($user2, NewDeposit::class);
    }

    // Admin single payment with sendNotification=true dispatches NewTransaction
    public function test_admin_single_payment_dispatches_notification_when_enabled()
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['balance' => 50]);
        $this->actingAs($admin);

        Livewire::test(TransactionsCrud::class)
            ->set('transaction.user_id', $user->id)
            ->set('transaction.name', 'Manual charge')
            ->set('transaction.price', 15.0)
            ->set('creatingPaymentTab', 'single')
            ->set('sendNotification', true)
            ->call('createPayment')
            ->assertHasNoErrors();

        Notification::assertSentTo($user, NewTransaction::class);
    }

    // Admin multiple payments notify each recipient
    public function test_admin_multiple_payments_notify_each_recipient()
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $user1 = User::factory()->create(['firstname' => 'Aalice', 'balance' => 50]);
        $user2 = User::factory()->create(['firstname' => 'Zbob', 'balance' => 50]);
        $this->actingAs($admin);

        $users = User::where('active', true)->orderByRaw('active DESC, LOWER(firstname)')->get();
        $amounts = array_fill(0, $users->count(), '');
        $amounts[$users->search(fn ($u) => $u->id === $user1->id)] = '10.00';
        $amounts[$users->search(fn ($u) => $u->id === $user2->id)] = '5.00';

        Livewire::test(TransactionsCrud::class)
            ->set('transaction.name', 'Bulk charge')
            ->set('creatingPaymentTab', 'multiple')
            ->set('sendNotification', true)
            ->set('userAmounts', $amounts)
            ->call('createPayment')
            ->assertHasNoErrors();

        Notification::assertSentTo($user1, NewTransaction::class);
        Notification::assertSentTo($user2, NewTransaction::class);
    }

    // Admin post notifies subscribed users and skips unsubscribed users
    public function test_admin_post_notifies_subscribed_users_only()
    {
        Notification::fake();
        $manager = User::factory()->manager()->create();
        $subscriber = User::factory()->create(['notify_new_posts' => true]);
        $nonSubscriber = User::factory()->create(['notify_new_posts' => false]);
        $this->actingAs($manager);

        Livewire::test(PostsCrud::class)
            ->set('post.title', 'Big announcement')
            ->set('post.body', 'Important news')
            ->set('sendNotification', true)
            ->call('createPost')
            ->assertHasNoErrors();

        Notification::assertSentTo($subscriber, NewPost::class);
        Notification::assertNotSentTo($nonSubscriber, NewPost::class);
    }

    // Admin post with sendNotification=false sends no notifications
    public function test_admin_post_suppresses_all_notifications_when_disabled()
    {
        Notification::fake();
        $manager = User::factory()->manager()->create();
        $subscriber = User::factory()->create(['notify_new_posts' => true]);
        $this->actingAs($manager);

        Livewire::test(PostsCrud::class)
            ->set('post.title', 'Silent post')
            ->set('post.body', 'No one will be notified')
            ->set('sendNotification', false)
            ->call('createPost')
            ->assertHasNoErrors();

        Notification::assertNotSentTo($subscriber, NewPost::class);
    }
}
