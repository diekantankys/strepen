<?php

namespace App\Notifications;

use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewDeposit extends Notification
{
    use Queueable;

    public $transaction;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
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
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Nieuwe storting op het Strepen Systeem')
            ->greeting('Beste '.$this->transaction->user->name.',')
            ->line('Er is een storting van '.Setting::get('currency_symbol').' '.number_format($this->transaction->price, 2, ',', '.').' op uw account gezet!')
            ->line('Uw balans is op dit moment nu '.Setting::get('currency_symbol').' '.number_format($this->transaction->user->balance, 2, ',', '.').'.')
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
            'transaction_id' => $this->transaction->id,
            'amount' => $this->transaction->price,
        ];
    }
}
