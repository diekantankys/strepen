<?php

namespace App\Http\Livewire\Games\Concerns;

use App\Models\Game;
use App\Models\GameScore;
use Illuminate\Support\Facades\Auth;

trait HandlesGameOverScores
{
    public bool $showModal = false;

    public int $score = 0;

    public $selectedUserId = null;

    public bool $isKiosk = false;

    public ?int $persistedScoreId = null;

    public $persistedScoreUserId = null;

    abstract protected function gameSlug(): string;

    public function mountGameOverScores(): void
    {
        $this->isKiosk = ! Auth::check() || Auth::id() === 1;

        if (! $this->isKiosk) {
            $this->selectedUserId = Auth::id();
        }
    }

    public function inputValue(string $name, $value): void
    {
        if ($name !== 'game_user') {
            return;
        }

        $this->selectedUserId = $value;

        if ($this->showModal) {
            $this->persistScore();
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

    protected function game(): Game
    {
        return Game::where('slug', $this->gameSlug())->firstOrFail();
    }

    protected function monthlyLeaderboard()
    {
        return GameScore::with('user')
            ->where('game_id', $this->game()->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->orderByDesc('score')
            ->limit(10)
            ->get();
    }

    private function persistScore(): void
    {
        $game = $this->game();
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
}
