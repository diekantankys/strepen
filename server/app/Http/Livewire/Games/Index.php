<?php

namespace App\Http\Livewire\Games;

use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        return view('livewire.games.index')
            ->layout('layouts.app', ['title' => __('games.title')]);
    }
}
