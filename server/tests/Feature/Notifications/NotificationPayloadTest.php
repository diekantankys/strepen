<?php

namespace Tests\Feature\Notifications;

use App\Models\Post;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\LowBalance;
use App\Notifications\NewDeposit;
use App\Notifications\NewPost;
use Tests\TestCase;

class NotificationPayloadTest extends TestCase
{
    public function test_notification_payloads_and_channels()
    {
        $user = User::factory()->create(['balance' => -3.5]);
        $transaction = Transaction::factory()->for($user)->create(['price' => 12.75]);
        $post = Post::factory()->for($user)->create();

        $lowBalance = new LowBalance($user);
        $newDeposit = new NewDeposit($transaction);
        $newPost = new NewPost($user, $post);

        $this->assertSame(['database', 'mail'], $lowBalance->via($user));
        $this->assertSame(['balance' => -3.5], $lowBalance->toArray($user));

        $this->assertSame(['database', 'mail'], $newDeposit->via($user));
        $this->assertSame([
            'transaction_id' => $transaction->id,
            'amount' => 12.75,
        ], $newDeposit->toArray($user));

        $this->assertSame(['database', 'mail'], $newPost->via($user));
        $this->assertSame(['post_id' => $post->id], $newPost->toArray($user));

        $this->assertSame('Te lage krediet op het Strepen Systeem', $lowBalance->toMail($user)->subject);
        $this->assertSame('Nieuwe storting op het Strepen Systeem', $newDeposit->toMail($user)->subject);
        $this->assertStringContainsString($post->title, $newPost->toMail($user)->subject);
    }
}
