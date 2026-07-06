<?php

namespace App\Http\Livewire\Games;

use App\Http\Livewire\Games\Concerns\HandlesGameOverScores;
use Livewire\Component;

class FlappyBird extends Component
{
    use HandlesGameOverScores;

    protected $listeners = ['gameOver' => 'handleGameOver', 'inputValue', 'game-over-play-again' => 'playAgain'];

    public function mount(): void
    {
        $this->mountGameOverScores();
    }

    protected function gameSlug(): string
    {
        return 'flappy-bird';
    }

    public function render()
    {
        $leaderboard = $this->monthlyLeaderboard();

        return view('livewire.games.flappy-bird', compact('leaderboard'))
            ->layout('layouts.app', ['title' => __('games.flappy_bird'), 'hideFooter' => true]);
    }
}
