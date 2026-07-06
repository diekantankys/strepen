<?php

namespace App\Http\Livewire\Games;

use App\Http\Livewire\Games\Concerns\HandlesGameOverScores;
use App\Models\Game;
use App\Models\GameAsset;
use Livewire\Component;

class Wanted extends Component
{
    use HandlesGameOverScores;

    protected $listeners = ['gameOver' => 'handleGameOver', 'inputValue', 'game-over-play-again' => 'playAgain'];

    public function mount(): void
    {
        $this->mountGameOverScores();
    }

    protected function gameSlug(): string
    {
        return 'wanted';
    }

    public function render()
    {
        $game = Game::where('slug', 'wanted')->firstOrFail();

        $faces = GameAsset::where('game_id', $game->id)->get()->map(fn ($a) => [
            'name' => $a->name,
            'url' => asset('storage/game-assets/'.$a->image_path),
        ])->values();

        $leaderboard = $this->monthlyLeaderboard();

        return view('livewire.games.wanted', compact('faces', 'leaderboard'))
            ->layout('layouts.app', ['title' => __('games.wanted'), 'hideFooter' => true]);
    }
}
