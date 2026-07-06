<?php

namespace App\Http\Livewire\Games;

use App\Http\Livewire\Games\Concerns\HandlesGameOverScores;
use Livewire\Component;

class CrossyRoad extends Component
{
    use HandlesGameOverScores;

    protected $listeners = ['gameOver' => 'handleGameOver', 'inputValue', 'game-over-play-again' => 'playAgain'];

    public function mount(): void
    {
        $this->mountGameOverScores();
    }

    protected function gameSlug(): string
    {
        return 'crossy-road';
    }

    public function render()
    {
        $leaderboard = $this->monthlyLeaderboard();

        return view('livewire.games.crossy-road', compact('leaderboard'))
            ->layout('layouts.app', ['title' => __('games.crossy_road'), 'hideFooter' => true]);
    }
}
