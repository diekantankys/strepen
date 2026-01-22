<?php

namespace App\Http\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class ChangePassword extends Component
{
    public $currentPassword;

    public $password;

    public $passwordConfirmation;

    public $isChanged;

    public $rules = [
        'currentPassword' => 'required|current_password',
        'password' => 'required|min:6',
        'passwordConfirmation' => 'required|same:password',
    ];

    public function mount()
    {
        $this->currentPassword = null;
        $this->password = null;
        $this->passwordConfirmation = null;
        $this->isChanged = false;
    }

    public function changePassword()
    {
        $this->validate();

        $user = Auth::user();
        $user->password = Hash::make($this->password);
        $user->save();

        $this->mount();
        $this->isChanged = true;
    }

    public function render()
    {
        return view('livewire.settings.change-password');
    }
}
