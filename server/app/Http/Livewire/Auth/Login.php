<?php

namespace App\Http\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public $email;

    public $password;

    public $rules = [
        'email' => 'required|email|exists:users,email',
        'password' => 'required',
    ];

    public function login()
    {
        $this->validate();

        // Check if user is active and not deleted
        $user = User::withTrashed()->where('email', $this->email)->first();
        if ($user->deleted_at != null) {
            $this->addError('email', __('auth.login.deleted_error'));
            $this->addError('password', 'null');

            return;
        }
        if (! $user->active) {
            $this->addError('email', __('auth.login.active_error'));
            $this->addError('password', 'null');

            return;
        }

        // Try to login user and remember in cookie
        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], true)) {
            $this->addError('email', __('auth.login.login_error'));
            $this->addError('password', 'null');

            return;
        }

        session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('layouts.app', ['title' => __('auth.login.title')]);
    }
}
