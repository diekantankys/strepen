<?php

namespace App\Http\Livewire\Games;

use App\Models\Game;
use App\Models\GameScore;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FlappyBird extends Component
{
    public bool $showModal = false;

    public int $score = 0;

    public $selectedUserId = null;

    public bool $isKiosk = false;

    protected $listeners = ['gameOver' => 'handleGameOver', 'inputValue'];

    public function mount(): void
    {
        $this->isKiosk = ! Auth::check() || Auth::id() === 1;

        if (! $this->isKiosk) {
            $this->selectedUserId = Auth::id();
        }
    }

    public function inputValue(string $name, $value): void
    {
        if ($name === 'game_user') {
            $this->selectedUserId = $value;
        }
    }

    public function handleGameOver(int $score): void
    {
        $this->score = $score;
        $this->showModal = true;
    }

    public function playAgain(): void
    {
        $this->persistScore();
        $this->showModal = false;
        $this->dispatch('game-restart');
    }

    private function persistScore(): void
    {
        $game = Game::where('slug', 'flappy-bird')->firstOrFail();

        $existing = GameScore::where('game_id', $game->id)
            ->where('user_id', $this->selectedUserId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->orderByDesc('score')
            ->first();

        if ($existing && $this->score > $existing->score) {
            $existing->delete();
        }

        if (! $existing || $this->score > $existing->score) {
            GameScore::create([
                'game_id' => $game->id,
                'user_id' => $this->selectedUserId,
                'score' => $this->score,
            ]);
        }
    }

    public function render()
    {
        $game = Game::where('slug', 'flappy-bird')->firstOrFail();

        $leaderboard = GameScore::with('user')
            ->where('game_id', $game->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->orderByDesc('score')
            ->limit(10)
            ->get();

        return view('livewire.games.flappy-bird', compact('leaderboard'))
            ->layout('layouts.app', ['title' => __('games.flappy_bird'), 'hideFooter' => true]);
    }
}
