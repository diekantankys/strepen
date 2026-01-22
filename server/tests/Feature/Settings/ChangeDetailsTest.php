<?php

namespace Tests\Feature\Settings;

use App\Http\LiveWire\Settings\ChangeDetails;
use App\Models\User;
use Livewire;
use Tests\TestCase;

class ChangeDetailsTest extends TestCase
{
    // Test change name
    public function test_change_name()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $newFirstname = $this->faker->firstname;
        $newInsertion = $this->faker->randomElement(['', '', '', 'van', 'de', 'van der']);
        $newLastname = $this->faker->lastname;
        Livewire::test(ChangeDetails::class)
            ->set('user.firstname', $newFirstname)
            ->set('user.insertion', $newInsertion)
            ->set('user.lastname', $newLastname)
            ->call('changeDetails')
            ->assertHasNoErrors();

        $user = User::find($user->id);
        $this->assertTrue($user->firstname == $newFirstname);
        $this->assertTrue($user->insertion == $newInsertion);
        $this->assertTrue($user->lastname == $newLastname);
    }

    // Test change email unique
    public function test_change_email_unique()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user2 = User::factory()->create();

        $newEmail = $user2->email;
        Livewire::test(ChangeDetails::class)
            ->set('user.email', $newEmail)
            ->call('changeDetails')
            ->assertHasErrors();
    }

    // Test change email
    public function test_change_email()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $newEmail = $this->faker->email;
        Livewire::test(ChangeDetails::class)
            ->set('user.email', $newEmail)
            ->call('changeDetails')
            ->assertHasNoErrors();

        $user = User::find($user->id);
        $this->assertTrue($user->email == $newEmail);
    }
}
