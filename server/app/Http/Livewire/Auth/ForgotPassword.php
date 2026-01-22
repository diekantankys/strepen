<?php

namespace App\Http\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Component;

class ForgotPassword extends Component
{
    public $email;

    public $isSend = false;

    public $isError = false;

    public $rules = [
        'email' => 'required|email|exists:users,email',
    ];

    public function forgotPassword()
    {
        $this->validate();

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status == Password::RESET_LINK_SENT) {
            $this->isSend = true;
            $this->isError = false;
            $this->email = null;
        } else {
            $this->isSend = false;
            $this->isError = true;
        }
    }

    public function render()
    {
        return view('livewire.auth.forgot-password')
            ->layout('layouts.app', ['title' => __('auth.forgot_password.title')]);
    }
}
