<?php

namespace Tests\Feature\Settings;

use App\Http\LiveWire\Settings\ChangeThanks;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire;
use Tests\TestCase;

class ChangeThanksTest extends TestCase
{
    // Test thanks file type valiation
    public function test_thanks_file_type_validation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->assertTrue($user->thanks == null);

        $wrongFiles = ['thanks.mp4', 'thanks.webp', 'thanks.mkv'];
        foreach ($wrongFiles as $file) {
            Livewire::test(ChangeThanks::class)
                ->set('thanks', UploadedFile::fake()->image($file))
                ->call('changeThanks')
                ->assertHasErrors();
        }

        $goodFiles = ['thanks.gif'];
        foreach ($goodFiles as $file) {
            Livewire::test(ChangeThanks::class)
                ->set('thanks', UploadedFile::fake()->image($file))
                ->call('changeThanks')
                ->assertHasNoErrors();
        }
    }

    // Test thanks file type upload
    public function test_thanks_file_upload()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->assertTrue($user->thanks == null);

        Livewire::test(ChangeThanks::class)
            ->set('thanks', UploadedFile::fake()->image('thanks.gif'))
            ->call('changeThanks')
            ->assertHasNoErrors();

        $this->assertTrue($user->thanks != null);
    }
}
