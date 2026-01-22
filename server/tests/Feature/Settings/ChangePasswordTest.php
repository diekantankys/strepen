<?php

namespace Tests\Feature\Settings;

use App\Http\LiveWire\Settings\ChangePassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    // Test change password wrong current password
    public function test_change_password_wrong_current_password()
    {
        $password = $this->faker->password;
        $user = User::factory()->password($password)->create();
        $this->actingAs($user);

        $newPassword = $this->faker->password;
        Livewire::test(ChangePassword::class)
            ->set('currentPassword', $this->faker->password)
            ->set('password', $newPassword)
            ->set('passwordConfirmation', $newPassword)
            ->call('changePassword')
            ->assertHasErrors();
    }

    // Test change password wrong confirmation
    public function test_change_password_wrong_confirmation()
    {
        $password = $this->faker->password;
        $user = User::factory()->password($password)->create();
        $this->actingAs($user);

        $newPassword = $this->faker->password;
        Livewire::test(ChangePassword::class)
            ->set('currentPassword', $password)
            ->set('password', $newPassword)
            ->set('passwordConfirmation', $this->faker->password)
            ->call('changePassword')
            ->assertHasErrors();
    }

    // Test change password
    public function test_change_password()
    {
        $password = $this->faker->password;
        $user = User::factory()->password($password)->create();
        $this->actingAs($user);

        $newPassword = $this->faker->password;
        Livewire::test(ChangePassword::class)
            ->set('currentPassword', $password)
            ->set('password', $newPassword)
            ->set('passwordConfirmation', $newPassword)
            ->call('changePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check($newPassword, $user->password));
    }
}
