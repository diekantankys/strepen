<?php

namespace App\Channels;

use App\Models\FcmToken;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Throwable;

class FcmChannel
{
    public function send($notifiable, Notification $notification): void
    {
        if (blank(config('firebase.projects.app.credentials'))) {
            return;
        }

        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        $tokens = $notifiable->fcmTokens;
        if ($tokens->isEmpty()) {
            return;
        }

        $result = $notification->toFcm($notifiable);
        $fcmNotification = FcmNotification::create($result[0], $result[1]);
        $data = $result[2] ?? [];

        $messages = $tokens
            ->map(fn (FcmToken $t) => CloudMessage::new()
                ->withToken($t->token)
                ->withNotification($fcmNotification)
                ->withData($data))
            ->all();

        try {
            $report = app('firebase.messaging')->sendAll($messages);

            $stale = array_merge($report->unknownTokens(), $report->invalidTokens());
            if (! empty($stale)) {
                FcmToken::whereIn('token', $stale)->delete();
            }
        } catch (Throwable) {
            // Network/auth errors - tokens are not stale, skip cleanup
        }
    }
}
