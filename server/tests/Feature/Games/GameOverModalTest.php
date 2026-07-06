<?php

namespace Tests\Feature\Games;

use App\Http\Livewire\Games\GameOverModal;
use App\Models\Game;
use App\Models\GameScore;
use App\Models\User;
use Livewire;
use Tests\TestCase;

class GameOverModalTest extends TestCase
{
    public function test_modal_renders_game_over_details_for_each_game()
    {
        $game = Game::where('slug', 'flappy-bird')->firstOrFail();
        $user = User::factory()->create();
        $entry = GameScore::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'score' => 42,
        ])->load('user');

        foreach (['flappy_bird', 'crossy_road', 'wanted'] as $translationPrefix) {
            Livewire::test(GameOverModal::class, [
                'showModal' => true,
                'score' => 42,
                'isKiosk' => false,
                'selectedUserId' => $user->id,
                'leaderboard' => collect([$entry]),
                'translationPrefix' => $translationPrefix,
            ])
                ->assertSee(__("games.{$translationPrefix}_game_over"))
                ->assertSee(__("games.{$translationPrefix}_monthly_leaderboard"))
                ->assertSee(__($translationPrefix === 'wanted' ? 'games.wanted_score_column' : "games.{$translationPrefix}_score_label"))
                ->assertSee('42')
                ->assertSee($user->name);
        }
    }

    public function test_modal_renders_kiosk_user_chooser()
    {
        Livewire::test(GameOverModal::class, [
            'showModal' => true,
            'score' => 0,
            'isKiosk' => true,
            'leaderboard' => collect(),
            'translationPrefix' => 'flappy_bird',
        ])
            ->assertSee(__('components.user_chooser.anonymous'))
            ->assertSee(__('games.flappy_bird_no_scores_yet'));
    }

    public function test_play_again_dispatches_parent_event()
    {
        Livewire::test(GameOverModal::class, [
            'leaderboard' => collect(),
            'translationPrefix' => 'wanted',
        ])
            ->call('playAgain')
            ->assertDispatched('game-over-play-again');
    }
}
