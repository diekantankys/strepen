<?php

namespace Tests\Feature\Settings;

use App\Http\LiveWire\Settings\ChangeAvatar;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire;
use Tests\TestCase;

class ChangeAvatarTest extends TestCase
{
    // Test avatar file type valiation
    public function test_avatar_file_type_validation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->assertTrue($user->avatar == null);

        $wrongFiles = ['avatar.bmp', 'avatar.gif', 'avatar.webp'];
        foreach ($wrongFiles as $file) {
            Livewire::test(ChangeAvatar::class)
                ->set('avatar', UploadedFile::fake()->image($file))
                ->call('changeAvatar')
                ->assertHasErrors();
        }

        $goodFiles = ['avatar.jpg', 'avatar.jpeg', 'avatar.png'];
        foreach ($goodFiles as $file) {
            Livewire::test(ChangeAvatar::class)
                ->set('avatar', UploadedFile::fake()->image($file))
                ->call('changeAvatar')
                ->assertHasNoErrors();
        }
    }

    // Test avatar file type upload
    public function test_avatar_file_upload()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->assertTrue($user->avatar == null);

        Livewire::test(ChangeAvatar::class)
            ->set('avatar', UploadedFile::fake()->image('avatar.png'))
            ->call('changeAvatar')
            ->assertHasNoErrors();

        $this->assertTrue($user->avatar != null);
    }
}
