<?php

namespace App\Http\Livewire\Admin\Users;

use App\Http\Livewire\PaginationComponent;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\WithFileUploads;

class Crud extends PaginationComponent
{
    use WithFileUploads;

    public $role;

    public $user;

    public $avatar;

    public $thanks;

    public $isCreating;

    public $isChecking;

    public $isExporting;

    public $exportTab;

    public $queryString = [
        'sort_by' => ['except' => ''],
        'query' => ['except' => ''],
        'role' => ['except' => ''],
    ];

    public function rules()
    {
        $rules = [
            'user.firstname' => 'required|min:2|max:48',
            'user.insertion' => 'nullable|max:16',
            'user.lastname' => 'required|min:2|max:48',
            'user.gender' => 'nullable|integer|digits_between:'.User::GENDER_MALE.','.User::GENDER_OTHER,
            'user.birthday' => 'nullable|date',
            'user.email' => 'required|email|max:255|unique:users,email',
            'user.phone' => 'nullable|max:255',
            'user.address' => 'nullable|min:2|max:255',
            'user.postcode' => 'nullable|min:2|max:32',
            'user.city' => 'nullable|min:2|max:255',
            'user._password' => 'required|min:6',
            'user.password_confirmation' => 'required|same:user._password',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
            'thanks' => 'nullable|image|mimes:gif|max:2048',
            'user.language' => 'required|integer|digits_between:'.User::LANGUAGE_ENGLISH.','.User::LANGUAGE_DUTCH,
            'user.theme' => 'required|integer|digits_between:'.User::THEME_LIGHT.','.User::THEME_SYSTEM,
            'user.receive_news' => 'nullable|boolean',
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
        if (
            $this->sort_by != 'lastname_desc' && $this->sort_by != 'firstname' && $this->sort_by != 'firstname_desc' &&
            $this->sort_by != 'created_at_desc' && $this->sort_by != 'created_at' && $this->sort_by != 'balance_desc' &&
            $this->sort_by != 'balance'
        ) {
            $this->sort_by = null;
        }

        if ($this->role != 'normal' && $this->role != 'manager' && $this->role != 'admin') {
            $this->role = null;
        }

        $this->user = new User;
        $this->avatar = null;
        $this->thanks = null;
        $this->isCreating = false;
        $this->isChecking = false;
        $this->isExporting = false;
        $this->exportTab = 'everyone';
    }

    public function search()
    {
        if ($this->role != 'normal' && $this->role != 'manager' && $this->role != 'admin') {
            $this->role = null;
        }
        $this->resetPage();
    }

    public function createUser()
    {
        $this->validate();

        $this->user->password = Hash::make($this->user->_password);
        unset($this->user->_password);
        unset($this->user->password_confirmation);

        if ($this->avatar != null) {
            $avatarName = User::generateAvatarName($this->avatar->extension());
            $this->avatar->storeAs('public/avatars', $avatarName);
            $this->user->avatar = $avatarName;
        }

        if ($this->thanks != null) {
            $thanksName = User::generateThanksName($this->thanks->extension());
            $this->thanks->storeAs('public/thanks', $thanksName);
            $this->user->thanks = $thanksName;
        }

        $this->user->save();
        $this->mount();
    }

    public function recalculateBalances()
    {
        User::withTrashed()->chunk(50, function ($users) {
            foreach ($users as $user) {
                $user->recalculateBalance();
                $user->save();
            }
        });
    }

    public function checkBalances()
    {
        User::checkBalances();
        $this->isChecking = false;
    }

    public function render()
    {
        $users = User::search(User::select(), $this->query);
        if ($this->role != null) {
            $role = null;
            if ($this->role == 'normal') {
                $role = User::ROLE_NORMAL;
            }
            if ($this->role == 'manager') {
                $role = User::ROLE_MANAGER;
            }
            if ($this->role == 'admin') {
                $role = User::ROLE_ADMIN;
            }
            $users = $users->where('role', $role);
        }

        if ($this->sort_by == null) {
            $users = $users->orderByRaw('active DESC, lastname');
        }
        if ($this->sort_by == 'lastname_desc') {
            $users = $users->orderByRaw('active DESC, lastname DESC');
        }
        if ($this->sort_by == 'firstname') {
            $users = $users->orderByRaw('active DESC, firstname');
        }
        if ($this->sort_by == 'firstname_desc') {
            $users = $users->orderByRaw('active DESC, firstname DESC');
        }
        if ($this->sort_by == 'created_at_desc') {
            $users = $users->orderBy('created_at', 'DESC');
        }
        if ($this->sort_by == 'created_at') {
            $users = $users->orderBy('created_at');
        }
        if ($this->sort_by == 'balance_desc') {
            $users = $users->orderBy('balance', 'DESC');
        }
        if ($this->sort_by == 'balance') {
            $users = $users->orderBy('balance');
        }

        return view('livewire.admin.users.crud', [
            'users' => $users->paginate(Setting::get('pagination_rows') * 4)->withQueryString(),
        ])->layout('layouts.app', ['title' => __('admin/users.crud.title'), 'chartjs' => true]);
    }
}
