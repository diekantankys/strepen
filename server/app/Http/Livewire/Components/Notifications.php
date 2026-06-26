<?php

namespace App\Http\Livewire\Components;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Notifications extends Component
{
    public function readNotification($notificationId)
    {
        Auth::user()->unreadNotifications()
            ->whereKey($notificationId)
            ->update(['read_at' => now()]);
    }

    public function render()
    {
        return view('livewire.components.notifications', [
            'notifications' => Auth::user()->unreadNotifications()
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
