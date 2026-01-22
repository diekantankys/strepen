<?php

namespace App\Http\Livewire\Settings;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class ChangeAvatar extends Component
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

        // Delete old user avatar
        if (Auth::user()->avatar != null) {
            Storage::delete('public/avatars/'.Auth::user()->avatar);
        }

        // Update user that he has an avatar
        $user = Auth::user();
        $user->avatar = $avatarName;
        $user->save();

        $this->avatar = null;
        $this->isChanged = true;
    }

    public function deleteAvatar()
    {
        // Delete user avatar file from storage
        Storage::delete('public/avatars/'.Auth::user()->avatar);

        // Update user that he has no avatar
        $user = Auth::user();
        $user->avatar = null;
        $user->save();

        $this->isDeleted = true;
    }

    public function render()
    {
        return view('livewire.settings.change-avatar');
    }
}
