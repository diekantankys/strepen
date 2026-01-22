<?php

namespace App\Http\Livewire\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Component;

class ResetPassword extends Component
{
    public $token;

    public $email;

    public $password;

    public $passwordConfirmation;

    public $isReset = false;

    public $isError = false;

    public $rules = [
        'token' => 'required',
        'email' => 'required|email|exists:users,email',
        'password' => 'required|min:6',
        'passwordConfirmation' => 'required|same:password',
    ];

    protected $queryString = ['email'];

    public function mount($token)
    {
        $this->token = $token;
    }

    public function resetPassword()
    {
        $this->validate();

        $status = Password::reset([
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->passwordConfirmation,
            'token' => $this->token,
        ], function ($user, $password) {
            $user->password = Hash::make($password);
            $user->setRememberToken(Str::random(60));
            $user->save();
            event(new PasswordReset($user));
        });

        if ($status == Password::PASSWORD_RESET) {
            $this->isReset = true;
            $this->isError = false;
            $this->password = null;
            $this->passwordConfirmation = null;
        } else {
            $this->isReset = false;
            $this->isError = true;
        }
    }

    public function render()
    {
        return view('livewire.auth.reset-password')
            ->layout('layouts.app', ['title' => __('auth.reset_password.title')]);
    }
}
