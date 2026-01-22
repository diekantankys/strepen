<?php

namespace App\Http\Livewire\Settings;

use App\Models\Setting;
use App\Models\User;
use DateTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ChangeDetails extends Component
{
    public $user;

    public $oldUserBirthday;

    public $isChanged = false;

    public function rules()
    {
        return [
            'user.firstname' => 'required|min:2|max:48',
            'user.insertion' => 'nullable|max:16',
            'user.lastname' => 'required|min:2|max:48',
            'user.gender' => 'nullable|integer|digits_between:'.User::GENDER_MALE.','.User::GENDER_OTHER,
            'user.birthday' => 'nullable|date',
            'user.email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore(Auth::user()->email, 'email'),
            ],
            'user.phone' => 'nullable|max:255',
            'user.address' => 'nullable|min:2|max:255',
            'user.postcode' => 'nullable|min:2|max:32',
            'user.city' => 'nullable|min:2|max:255',
            'user.language' => 'required|integer|digits_between:'.User::LANGUAGE_ENGLISH.','.User::LANGUAGE_DUTCH,
            'user.theme' => 'required|integer|digits_between:'.User::THEME_LIGHT.','.User::THEME_SYSTEM,
            'user.receive_news' => 'nullable|boolean',
        ];
    }

    public function mount()
    {
        $this->user = Auth::user();
        $this->oldUserBirthday = $this->user->birthday;
    }

    public function changeDetails()
    {
        $this->validate();

        if ($this->user->gender == '') {
            $this->user->gender = null;
        }
        if ($this->user->birthday.'' == date('Y-m-d H:i:s')) {
            $this->user->birthday = null;
        }

        if ($this->oldUserBirthday != null && $this->oldUserBirthday->diff(new DateTime('now'))->y < Setting::get('minor_age')) {
            $this->user->birthday = $this->oldUserBirthday;
        }
        $this->user->save();

        $this->isChanged = true;
    }

    public function render()
    {
        return view('livewire.settings.change-details');
    }
}
