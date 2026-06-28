<?php

namespace App\Http\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ChangeNotifications extends Component
{
    public $user;

    public $isChanged = false;

    public function rules()
    {
        return [
            'user.notify_new_posts' => 'nullable|boolean',
            'user.notify_low_balance' => 'nullable|boolean',
            'user.notify_new_deposits' => 'nullable|boolean',
            'user.notify_new_transactions' => 'nullable|boolean',
            'user.notify_by_email' => 'nullable|boolean',
        ];
    }

    public function mount()
    {
        $this->user = Auth::user();
    }

    public function changeNotifications()
    {
        $this->validate();
        $this->user->save();
        $this->isChanged = true;
    }

    public function render()
    {
        return view('livewire.settings.change-notifications');
    }
}
