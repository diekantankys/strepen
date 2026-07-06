<?php

namespace App\Http\Livewire\Games;

use Livewire\Attributes\Reactive;
use Livewire\Component;

class GameOverModal extends Component
{
    #[Reactive]
    public bool $showModal = false;

    #[Reactive]
    public int $score = 0;

    #[Reactive]
    public bool $isKiosk = false;

    #[Reactive]
    public $selectedUserId = null;

    #[Reactive]
    public $leaderboard;

    public string $translationPrefix;

    public function playAgain(): void
    {
        $this->dispatch('game-over-play-again');
    }

    public function render()
    {
        return view('livewire.games.game-over-modal');
    }
}
