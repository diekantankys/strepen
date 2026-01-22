<?php

namespace App\Notifications;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowBalance extends Notification
{
    use Queueable;

    public $user;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
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
            ->subject('Te lage krediet op het Strepen Systeem')
            ->greeting('Beste '.$this->user->name.',')
            ->line('Na het invoeren van uw laatst gekochte producten op de stam is gebleken dat uw krediet lager dan '.Setting::get('currency_symbol').' '.number_format(Setting::get('min_user_balance'), 2, ',', '.').' is. Uw balans is op dit moment nu '.Setting::get('currency_symbol').' '.number_format($this->user->balance, 2, ',', '.').'.')
            ->line('Dit is volgens het stambestuur te weinig! We willen u dan ook vragen om zo snel mogelijk te verhogen! Dit kan door geld over te maken naar rekening '.Setting::get('bank_account_iban').' o.v.v. '.Setting::get('bank_account_holder').'.')
            ->line('Mocht u nog vragen hebben of denk u dat er iets niet klopt beantwoord dan dit mailtje.')
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
            'balance' => $this->user->balance,
        ];
    }
}
