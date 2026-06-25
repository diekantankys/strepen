<?php

namespace App\Notifications;

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

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user, Post $post)
    {
        $this->user = $user;
        $this->post = $post;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($this->post->title.' - Een nieuw nieuws bericht op het Strepen Systeem')
            ->greeting('Beste '.$this->user->name.',')
            ->line('Er is een nieuw nieuws bericht op het Strepen Systeem geplaatst:')
            ->line(new HtmlString(BetterParsedown::instance()->text($this->post->body)))
            ->salutation('Groetjes, het stambestuur');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'post_id' => $this->post->id,
        ];
    }
}
