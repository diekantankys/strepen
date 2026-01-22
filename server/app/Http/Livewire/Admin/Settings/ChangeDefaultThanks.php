<?php

namespace App\Http\Livewire\Admin\Settings;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class ChangeDefaultThanks extends Component
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

        // Delete old global thanks when not default
        if (Setting::get('default_user_thanks') != 'default.gif') {
            Storage::delete('public/thanks/'.Setting::get('default_user_thanks'));
        }

        // Update global thanks
        Setting::set('default_user_thanks', $thanksName);
        $this->thanks = null;
        $this->isChanged = true;
    }

    public function deleteThanks()
    {
        // Delete global thanks
        if (Setting::get('default_user_thanks') != 'default.gif') {
            Storage::delete('public/thanks/'.Setting::get('default_user_thanks'));
        }

        // Update global thanks to default one
        Setting::set('default_user_thanks', 'default.gif');
        $this->isDeleted = true;
    }

    public function render()
    {
        return view('livewire.admin.settings.change-default-thanks');
    }
}
