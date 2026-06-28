<?php

namespace Tests\Feature\Notifications;

use App\Channels\FcmChannel;
use App\Models\Post;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\LowBalance;
use App\Notifications\NewDeposit;
use App\Notifications\NewPost;
use App\Notifications\NewTransaction;
use Tests\TestCase;

class NotificationPayloadTest extends TestCase
{
    public function test_notification_via_channels_respect_user_preferences()
    {
        $user = User::factory()->create([
            'notify_new_posts' => true,
            'notify_low_balance' => true,
            'notify_new_deposits' => true,
            'notify_new_transactions' => true,
            'notify_by_email' => true,
        ]);
        $transaction = Transaction::factory()->for($user)->create(['price' => 5.0]);
        $post = Post::factory()->for($user)->create();

        $lowBalance = new LowBalance($user);
        $newDeposit = new NewDeposit($transaction);
        $newPost = new NewPost($user, $post);
        $newTransaction = new NewTransaction($transaction);

        // All preferences on: database + FCM + mail
        $allChannels = ['database', FcmChannel::class, 'mail'];
        $this->assertSame($allChannels, $lowBalance->via($user));
        $this->assertSame($allChannels, $newDeposit->via($user));
        $this->assertSame($allChannels, $newPost->via($user));
        $this->assertSame($allChannels, $newTransaction->via($user));

        // Per-type preference off: database only
        $user->notify_low_balance = false;
        $this->assertSame(['database'], $lowBalance->via($user));
        $user->notify_low_balance = true;

        $user->notify_new_deposits = false;
        $this->assertSame(['database'], $newDeposit->via($user));
        $user->notify_new_deposits = true;

        $user->notify_new_posts = false;
        $this->assertSame(['database'], $newPost->via($user));
        $user->notify_new_posts = true;

        $user->notify_new_transactions = false;
        $this->assertSame(['database'], $newTransaction->via($user));
        $user->notify_new_transactions = true;

        // Email off: database + FCM but no mail
        $user->notify_by_email = false;
        $fcmOnly = ['database', FcmChannel::class];
        $this->assertSame($fcmOnly, $lowBalance->via($user));
        $this->assertSame($fcmOnly, $newDeposit->via($user));
        $this->assertSame($fcmOnly, $newPost->via($user));
        $this->assertSame($fcmOnly, $newTransaction->via($user));
    }

    public function test_notification_payloads_and_mail_subjects()
    {
        $user = User::factory()->create(['balance' => -3.5]);
        $transaction = Transaction::factory()->for($user)->create(['price' => 12.75]);
        $post = Post::factory()->for($user)->create();

        $lowBalance = new LowBalance($user);
        $newDeposit = new NewDeposit($transaction);
        $newPost = new NewPost($user, $post);
        $newTransaction = new NewTransaction($transaction);

        $this->assertSame(['balance' => -3.5], $lowBalance->toArray($user));
        $this->assertSame(['transaction_id' => $transaction->id, 'amount' => 12.75], $newDeposit->toArray($user));
        $this->assertSame(['post_id' => $post->id], $newPost->toArray($user));
        $this->assertSame(['transaction_id' => $transaction->id, 'amount' => 12.75], $newTransaction->toArray($user));

        $this->assertSame('Te lage krediet op het Strepen Systeem', $lowBalance->toMail($user)->subject);
        $this->assertSame('Nieuwe storting op het Strepen Systeem', $newDeposit->toMail($user)->subject);
        $this->assertStringContainsString($post->title, $newPost->toMail($user)->subject);
        $this->assertIsString($newTransaction->toMail($user)->subject);
        $this->assertNotEmpty($newTransaction->toMail($user)->subject);
    }
}
