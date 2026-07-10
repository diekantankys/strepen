<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewTransaction extends Notification
{
    use Queueable;

    public $transaction;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function via($notifiable): array
    {
        if (! $notifiable->notify_new_transactions) {
            return [];
        }

        $channels = ['database', FcmChannel::class];
        if ($notifiable->notify_by_email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toFcm($notifiable): array
    {
        $isEn = app()->getLocale() === 'en';
        $amount = number_format($this->transaction->price, 2, $isEn ? '.' : ',', $isEn ? ',' : '.');

        return [
            __('notifications.new_transaction_fcm_title'),
            __('notifications.new_transaction_fcm_body', [
                'currency' => Setting::get('currency_symbol'),
                'amount' => $amount,
            ]),
            ['type' => 'new_transaction', 'notification_id' => $this->id],
        ];
    }

    public function toMail($notifiable)
    {
        $isEn = app()->getLocale() === 'en';
        $amount = number_format($this->transaction->price, 2, $isEn ? '.' : ',', $isEn ? ',' : '.');
        $balance = number_format($this->transaction->user->balance, 2, $isEn ? '.' : ',', $isEn ? ',' : '.');
        $currency = Setting::get('currency_symbol');

        return (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject(__('notifications.new_transaction_mail_subject'))
            ->greeting(__('notifications.greeting', ['name' => $this->transaction->user->name]))
            ->line(__('notifications.new_transaction_mail_line1', ['currency' => $currency, 'amount' => $amount]))
            ->line(__('notifications.new_transaction_mail_line2', ['currency' => $currency, 'balance' => $balance]))
            ->salutation(__('notifications.salutation'));
    }

    public function toArray($notifiable)
    {
        return [
            'transaction_id' => $this->transaction->id,
            'amount' => $this->transaction->price,
        ];
    }
}
