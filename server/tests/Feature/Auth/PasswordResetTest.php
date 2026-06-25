<?php

namespace Tests\Feature\Auth;

use App\Http\Livewire\Auth\ForgotPassword;
use App\Http\Livewire\Auth\ResetPassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    public function test_forgot_password_validates_email_and_sends_reset_link()
    {
        $user = User::factory()->create();

        Livewire::test(ForgotPassword::class)
            ->set('email', 'missing@example.com')
            ->call('forgotPassword')
            ->assertHasErrors(['email']);

        Livewire::test(ForgotPassword::class)
            ->set('email', $user->email)
            ->call('forgotPassword')
            ->assertHasNoErrors()
            ->assertSet('isSend', true)
            ->assertSet('email', null);
    }

    public function test_reset_password_rejects_bad_confirmation_and_resets_with_valid_token()
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', $user->email)
            ->set('password', 'new-secret')
            ->set('passwordConfirmation', 'different')
            ->call('resetPassword')
            ->assertHasErrors(['passwordConfirmation']);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', $user->email)
            ->set('password', 'new-secret')
            ->set('passwordConfirmation', 'new-secret')
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertSet('isReset', true)
            ->assertSet('password', null);

        $this->assertTrue(Hash::check('new-secret', $user->fresh()->password));
    }
}
