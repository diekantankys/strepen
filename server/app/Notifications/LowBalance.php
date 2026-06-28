<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowBalance extends Notification
{
    use Queueable;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function via($notifiable): array
    {
        if (! $notifiable->notify_low_balance) {
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
        $isEn = app()->getLocale() === 'en';
        $balance = number_format($this->user->balance, 2, $isEn ? '.' : ',', $isEn ? ',' : '.');

        return [
            __('notifications.low_balance_fcm_title'),
            __('notifications.low_balance_fcm_body', [
                'currency' => Setting::get('currency_symbol'),
                'balance' => $balance,
            ]),
            ['type' => 'low_balance', 'notification_id' => $this->id],
        ];
    }

    public function toMail($notifiable)
    {
        $isEn = app()->getLocale() === 'en';
        $balance = number_format($this->user->balance, 2, $isEn ? '.' : ',', $isEn ? ',' : '.');
        $minBalance = number_format(Setting::get('min_user_balance'), 2, $isEn ? '.' : ',', $isEn ? ',' : '.');
        $currency = Setting::get('currency_symbol');

        return (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject(__('notifications.low_balance_mail_subject'))
            ->greeting(__('notifications.greeting', ['name' => $this->user->name]))
            ->line(__('notifications.low_balance_mail_line1', [
                'currency' => $currency,
                'min_balance' => $minBalance,
                'balance' => $balance,
            ]))
            ->line(__('notifications.low_balance_mail_line2', [
                'iban' => Setting::get('bank_account_iban'),
                'holder' => Setting::get('bank_account_holder'),
            ]))
            ->line(__('notifications.low_balance_mail_line3'))
            ->salutation(__('notifications.salutation'));
    }

    public function toArray($notifiable)
    {
        return [
            'balance' => $this->user->balance,
        ];
    }
}
