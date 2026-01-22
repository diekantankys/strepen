<?php

namespace App\Http\Livewire\Admin\Settings;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class ChangeDefaultAvatar extends Component
{
    use WithFileUploads;

    public $avatar;

    public $isChanged = false;

    public $isDeleted = false;

    public $rules = [
        'avatar' => 'required|image|mimes:jpg,jpeg,png|max:1024',
    ];

    public function changeAvatar()
    {
        $this->validate();

        // Save file to avatars folder
        $avatarName = User::generateAvatarName($this->avatar->extension());
        $this->avatar->storeAs('public/avatars', $avatarName);

        // Delete old global avatar when not default
        if (Setting::get('default_user_avatar') != 'default.png') {
            Storage::delete('public/avatars/'.Setting::get('default_user_avatar'));
        }

        // Update global avatar
        Setting::set('default_user_avatar', $avatarName);
        $this->avatar = null;
        $this->isChanged = true;
    }

    public function deleteAvatar()
    {
        // Delete global avatar
        if (Setting::get('default_user_avatar') != 'default.png') {
            Storage::delete('public/avatars/'.Setting::get('default_user_avatar'));
        }

        // Update global avatar to default one
        Setting::set('default_user_avatar', 'default.png');
        $this->isDeleted = true;
    }

    public function render()
    {
        return view('livewire.admin.settings.change-default-avatar');
    }
}
