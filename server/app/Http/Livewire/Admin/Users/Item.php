<?php

namespace App\Http\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class Item extends Component
{
    use WithFileUploads;

    public $user;

    public $oldRole;

    public $newPassword;

    public $newPasswordConfirmation;

    public $avatar;

    public $thanks;

    public $isShowing = false;

    public $startDate;

    public $isEditing = false;

    public $isDeleting = false;

    public function rules()
    {
        $rules = [
            'user.firstname' => 'required|min:2|max:48',
            'user.insertion' => 'nullable|max:16',
            'user.lastname' => 'required|min:2|max:48',
            'user.gender' => 'nullable|integer|digits_between:'.User::GENDER_MALE.','.User::GENDER_OTHER,
            'user.birthday' => 'nullable|date',
            'user.email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user->email, 'email'),
            ],
            'user.phone' => 'nullable|max:255',
            'user.address' => 'nullable|min:2|max:255',
            'user.postcode' => 'nullable|min:2|max:32',
            'user.city' => 'nullable|min:2|max:255',
            'newPassword' => 'nullable|min:6',
            'newPasswordConfirmation' => $this->newPassword != null ? ['required', 'same:newPassword'] : [],
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
            'thanks' => 'nullable|image|mimes:gif|max:2048',
            'user.language' => 'required|integer|digits_between:'.User::LANGUAGE_ENGLISH.','.User::LANGUAGE_DUTCH,
            'user.theme' => 'required|integer|digits_between:'.User::THEME_LIGHT.','.User::THEME_SYSTEM,
            'user.receive_news' => 'nullable|boolean',
            'user.active' => 'nullable|boolean',
        ];
        if (Auth::user()->role == User::ROLE_MANAGER) {
            $rules['user.role'] = 'required|integer|digits_between:'.User::ROLE_NORMAL.','.User::ROLE_MANAGER;
        }
        if (Auth::user()->role == User::ROLE_ADMIN) {
            $rules['user.role'] = 'required|integer|digits_between:'.User::ROLE_NORMAL.','.User::ROLE_ADMIN;
        }

        return $rules;
    }

    public function mount()
    {
        $this->oldRole = $this->user->role;

        $firstTransaction = $this->user->transactions()->orderBy('created_at')->first();
        if ($firstTransaction != null) {
            $maxDiff = 365 * 24 * 60 * 60;
            if (time() - $firstTransaction->created_at->getTimestamp() < $maxDiff) {
                $this->startDate = $firstTransaction->created_at->format('Y-m-d');
            } else {
                $this->startDate = date('Y-m-d', time() - $maxDiff);
            }
        } else {
            $this->startDate = date('Y-m-d');
        }
    }

    public function editUser()
    {
        $this->validate();

        if ($this->user->gender == '') {
            $this->user->gender = null;
        }
        if ($this->user->birthday.'' == date('Y-m-d H:i:s')) {
            $this->user->birthday = null;
        }

        if ($this->newPassword != null) {
            $this->user->password = Hash::make($this->newPassword);
        }

        if ($this->avatar != null) {
            $avatarName = User::generateAvatarName($this->avatar->extension());
            $this->avatar->storeAs('public/avatars', $avatarName);

            if ($this->user->avatar != null) {
                Storage::delete('public/avatars/'.$this->user->avatar);
            }
            $this->user->avatar = $avatarName;
            $this->avatar = null;
        }

        if ($this->thanks != null) {
            $thanksName = User::generateAvatarName($this->thanks->extension());
            $this->thanks->storeAs('public/thanks', $thanksName);

            if ($this->user->thanks != null) {
                Storage::delete('public/thanks/'.$this->user->thanks);
            }
            $this->user->thanks = $thanksName;
            $this->thanks = null;
        }

        if (Auth::user()->role == User::ROLE_MANAGER && $this->oldRole == User::ROLE_ADMIN) {
            $this->user->role = User::ROLE_ADMIN;
        }

        $this->isEditing = false;
        $this->user->save();
        $this->dispatch('refresh')->to(Crud::class);

        $this->newPassword = null;
        $this->newPasswordConfirmation = null;
    }

    public function hijackUser()
    {
        Auth::login($this->user, true);

        return $this->redirectRoute('home', navigate: true);
    }

    public function deleteAvatar()
    {
        if ($this->user->avatar != null) {
            Storage::delete('public/avatars/'.$this->user->avatar);
        }
        $this->user->avatar = null;
        User::find($this->user->id)->update(['avatar' => null]);
        $this->dispatch('refresh')->to(Crud::class);
    }

    public function deleteThanks()
    {
        if ($this->user->thanks != null) {
            Storage::delete('public/thanks/'.$this->user->thanks);
        }
        $this->user->thanks = null;
        User::find($this->user->id)->update(['thanks' => null]);
        $this->dispatch('refresh')->to(Crud::class);
    }

    public function deleteUser()
    {
        $this->isDeleting = false;
        if (
            (Auth::user()->role == User::ROLE_MANAGER && $this->user->role != User::ROLE_ADMIN) ||
            Auth::user()->role == User::ROLE_ADMIN
        ) {
            $this->user->delete();
            $this->dispatch('refresh')->to(Crud::class);
        }
    }

    public function render()
    {
        return view('livewire.admin.users.item');
    }
}
