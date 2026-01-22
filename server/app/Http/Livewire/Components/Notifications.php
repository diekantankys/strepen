<?php

namespace App\Http\Livewire\Components;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Notifications extends Component
{
    public function readNotification($notificationId)
    {
        $notification = Notification::find($notificationId);
        if ($notification != null && $notification->notifiable_id == Auth::id()) {
            $notification->read_at = now();
            $notification->save();
        }
    }

    public function render()
    {
        unset(Auth::user()->unreadNotifications);

        return view('livewire.components.notifications', [
            'notifications' => Auth::user()->unreadNotifications->slice(0, 5),
        ]);
    }
}
