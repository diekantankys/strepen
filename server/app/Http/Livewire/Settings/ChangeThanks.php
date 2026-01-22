<?php

namespace App\Http\Livewire\Settings;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class ChangeThanks extends Component
{
    use WithFileUploads;

    public $thanks;

    public $isChanged = false;

    public $isDeleted = false;

    public $rules = [
        'thanks' => 'required|image|mimes:gif|max:2048',
    ];

    public function changeThanks()
    {
        $this->validate();

        // Save file to thanks folder
        $thanksName = User::generateThanksName($this->thanks->extension());
        $this->thanks->storeAs('public/thanks', $thanksName);

        // Delete old user thanks
        if (Auth::user()->thanks != null) {
            Storage::delete('public/thanks/'.Auth::user()->thanks);
        }

        // Update user that he has an thanks
        $user = Auth::user();
        $user->thanks = $thanksName;
        $user->save();

        $this->thanks = null;
        $this->isChanged = true;
    }

    public function deleteThanks()
    {
        // Delete user thanks file from storage
        Storage::delete('public/thanks/'.Auth::user()->thanks);

        // Update user that he has no thanks
        $user = Auth::user();
        $user->thanks = null;
        $user->save();

        $this->isDeleted = true;
    }

    public function render()
    {
        return view('livewire.settings.change-thanks');
    }
}
