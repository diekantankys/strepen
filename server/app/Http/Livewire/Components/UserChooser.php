<?php

namespace App\Http\Livewire\Components;

use App\Models\Transaction;
use App\Models\User;

class UserChooser extends InputComponent
{
    // Props
    public $userId;

    public $inline = false;

    public $relationship = false;

    public $includeInactive = false;

    public $includeStrepenUser = false;

    public $postsRequired = false;

    public $inventoriesRequired = false;

    public $sortBy = 'lastname';

    // State
    public $htmlInputId;

    public $users;

    public $filteredUsers;

    public $userName;

    public $user;

    public $isOpen = false;

    // Lifecycle
    public function mount()
    {
        $this->htmlInputId = uniqid();

        $users = User::select();
        if (! $this->includeInactive) {
            if ($this->includeStrepenUser) {
                $users = $users->where(fn ($query) => $query->where('active', true)->orWhere('id', 1));
            } else {
                $users = $users->where('active', true);
            }
        }
        if ($this->sortBy == 'lastname') {
            $users = $users->orderByRaw('active DESC, lastname');
        }
        if ($this->sortBy == 'balance_desc') {
            $users = $users->orderBy('balance', 'DESC');
        }
        $this->users = $users->withCount(['posts', 'inventories'])->get();
        $this->sortUsers();

        if ($this->postsRequired) {
            $this->users = $this->users->filter(fn ($user) => $user->posts_count > 0);
        }
        if ($this->inventoriesRequired) {
            $this->users = $this->users->filter(fn ($user) => $user->inventories_count > 0);
        }
        $this->filterUsers();

        if ($this->userId != null) {
            $this->selectUser($this->userId);
        }
    }

    public function sortUsers()
    {
        if ($this->sortBy == 'last_transaction') {
            $this->users = $this->users->map(function ($user) { // Very slow
                $lastTransaction = $user->transactions()
                    ->where('type', Transaction::TYPE_TRANSACTION)
                    ->orderBy('created_at', 'DESC')->first();
                $user->lastTransactionCreatedAt = $lastTransaction != null ? $lastTransaction->created_at : null;

                return $user;
            })->sortByDesc('lastTransactionCreatedAt')->values();
        }
    }

    public function filterUsers()
    {
        $this->filteredUsers = $this->users
            ->filter(fn ($user) => strlen($this->userName) == 0 || stripos($user->name, $this->userName) !== false)
            ->slice(0, 10);
    }

    public function emitValue()
    {
        $this->emitUp('inputValue', $this->name, $this->user != null ? $this->user->id : null);
    }

    public function render()
    {
        return view('livewire.components.user-chooser');
    }

    // Events
    public function inputValidate($name)
    {
        if ($this->name == $name) {
            $this->valid = $this->user != null;
        }
    }

    public function inputClear($name)
    {
        if ($this->name == $name) {
            $this->userName = '';
            $this->user = null;
            $this->emitValue();
            $this->sortUsers();
            $this->filterUsers();
            $this->isOpen = false;
        }
    }

    // Listeners
    public function updatedUserName()
    {
        $this->isOpen = true;
        if ($this->user != null && $this->userName != $this->user->name) {
            $this->user = null;
            $this->emitValue();
        }
        $this->filterUsers();
    }

    // Actions
    public function selectFirstUser()
    {
        if ($this->filteredUsers->count() > 0) {
            $this->selectUser($this->filteredUsers->first()->id);
        }
    }

    public function selectUser($userId)
    {
        $this->user = $this->users->find($userId);
        if ($this->user == null) {
            $this->user = User::withTrashed()->find($userId);
        }
        $this->userName = $this->user->name;
        $this->emitValue();
        $this->filterUsers();
        $this->isOpen = false;
    }
}
