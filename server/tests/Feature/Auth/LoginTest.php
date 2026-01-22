<?php

namespace Tests\Feature\Auth;

use App\Http\LiveWire\Auth\Login;
use App\Models\User;
use Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    // Test to login with right email and password
    public function test_login()
    {
        $password = $this->faker->password;
        $user = User::factory()->password($password)->create();
        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', $password)
            ->call('login')
            ->assertRedirect(route('home'));
    }

    // Test to login with wrong email
    public function test_login_wrong_email()
    {
        $password = $this->faker->password;
        $user = User::factory()->password($password)->create();
        Livewire::test(Login::class)
            ->set('email', $this->faker->email())
            ->set('password', $password)
            ->call('login')
            ->assertHasErrors();
    }

    // Test to login with wrong password
    public function test_login_wrong_password()
    {
        $user = User::factory()->create();
        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', $this->faker->password)
            ->call('login')
            ->assertHasErrors();
    }

    // Test to login with inactive user
    public function test_login_inactive_user()
    {
        $password = $this->faker->password;
        $user = User::factory()->password($password)->create();
        $user->active = false;
        $user->save();
        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', $password)
            ->call('login')
            ->assertHasErrors();
    }

    // Test to login with deleted user
    public function test_login_deleted_user()
    {
        $password = $this->faker->password;
        $user = User::factory()->password($password)->create();
        $user->delete();
        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', $password)
            ->call('login')
            ->assertHasErrors();
    }
}
