<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Helpers\BetterParsedown;
use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class NewPost extends Notification
{
    use Queueable;

    public $user;

    public $post;

    public function __construct(User $user, Post $post)
    {
        $this->user = $user;
        $this->post = $post;
    }

    public function via($notifiable): array
    {
        if (! $notifiable->notify_new_posts) {
            return ['database'];
        }

        $channels = ['database', FcmChannel::class];
        if ($notifiable->notify_by_email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toFcm($notifiable): array
    {
        return [
            __('notifications.new_post_fcm_title'),
            $this->post->title,
            ['type' => 'new_post', 'notification_id' => $this->id, 'post_id' => (string) $this->post->id],
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject(__('notifications.new_post_mail_subject', ['title' => $this->post->title]))
            ->greeting(__('notifications.greeting', ['name' => $this->user->name]))
            ->line(__('notifications.new_post_mail_line1'))
            ->line(new HtmlString(BetterParsedown::instance()->text($this->post->body)))
            ->salutation(__('notifications.salutation'));
    }

    public function toArray($notifiable)
    {
        return [
            'post_id' => $this->post->id,
        ];
    }
}
