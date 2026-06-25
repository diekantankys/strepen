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

    public ?int $persistedScoreId = null;

    public $persistedScoreUserId = null;

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

            if ($this->showModal) {
                $this->persistScore();
            }
        }
    }

    public function handleGameOver(int $score): void
    {
        $this->score = $score;
        $this->persistedScoreId = null;
        $this->persistedScoreUserId = null;
        $this->persistScore();
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
        $selectedUserId = $this->selectedUserId !== null ? (int) $this->selectedUserId : null;

        if ($this->persistedScoreId !== null && $this->sameUser($this->persistedScoreUserId, $selectedUserId)) {
            return;
        }

        if ($this->persistedScoreId !== null) {
            GameScore::where('id', $this->persistedScoreId)
                ->where('game_id', $game->id)
                ->where('score', $this->score)
                ->delete();

            $this->persistedScoreId = null;
            $this->persistedScoreUserId = null;
        }

        if ($selectedUserId === null) {
            $this->persistAnonymousScore($game);

            return;
        }

        $this->persistUserScore($game, $selectedUserId);
    }

    private function persistAnonymousScore(Game $game): void
    {
        $existing = GameScore::where('game_id', $game->id)
            ->whereNull('user_id')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->orderByDesc('score')
            ->first();

        if (! $existing || $this->score > $existing->score) {
            $score = GameScore::create([
                'game_id' => $game->id,
                'user_id' => null,
                'score' => $this->score,
            ]);

            $this->persistedScoreId = $score->id;
            $this->persistedScoreUserId = null;
        }
    }

    private function persistUserScore(Game $game, int $selectedUserId): void
    {
        $scores = GameScore::where('game_id', $game->id)
            ->where('user_id', $selectedUserId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->orderByDesc('score')
            ->orderBy('id')
            ->get();

        $existing = $scores->first();

        if ($existing) {
            $duplicateIds = $scores->skip(1)->pluck('id');
            if ($duplicateIds->isNotEmpty()) {
                GameScore::whereIn('id', $duplicateIds)->delete();
            }

            if ($this->score > $existing->score) {
                $existing->update(['score' => $this->score]);
            }

            $this->persistedScoreId = $existing->id;
            $this->persistedScoreUserId = $selectedUserId;

            return;
        }

        $score = GameScore::create([
            'game_id' => $game->id,
            'user_id' => $selectedUserId,
            'score' => $this->score,
        ]);

        $this->persistedScoreId = $score->id;
        $this->persistedScoreUserId = $selectedUserId;
    }

    private function sameUser($left, $right): bool
    {
        return ($left !== null ? (int) $left : null) === ($right !== null ? (int) $right : null);
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
