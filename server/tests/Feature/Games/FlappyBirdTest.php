<?php

namespace Tests\Feature\Games;

use App\Http\Livewire\Games\FlappyBird;
use App\Models\Game;
use App\Models\GameScore;
use App\Models\User;
use Livewire;
use Tests\TestCase;

class FlappyBirdTest extends TestCase
{
    public function test_game_over_event_opens_modal_with_score()
    {
        Livewire::test(FlappyBird::class)
            ->dispatch('gameOver', 7)
            ->assertSet('score', 7)
            ->assertSet('showModal', true);
    }

    public function test_play_again_saves_score_and_dispatches_restart()
    {
        $user = User::factory()->create();

        Livewire::test(FlappyBird::class)
            ->set('selectedUserId', $user->id)
            ->dispatch('gameOver', 12)
            ->call('playAgain')
            ->assertSet('showModal', false)
            ->assertDispatched('game-restart');

        $this->assertDatabaseHas('game_scores', [
            'game_id' => Game::where('slug', 'flappy-bird')->value('id'),
            'user_id' => $user->id,
            'score' => 12,
        ]);
    }

    public function test_only_best_monthly_score_is_kept()
    {
        $game = Game::where('slug', 'flappy-bird')->firstOrFail();
        $user = User::factory()->create();

        GameScore::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'score' => 10,
        ]);

        Livewire::test(FlappyBird::class)
            ->set('selectedUserId', $user->id)
            ->dispatch('gameOver', 15)
            ->call('playAgain');

        $this->assertDatabaseMissing('game_scores', [
            'game_id' => $game->id,
            'user_id' => $user->id,
            'score' => 10,
        ]);

        $this->assertDatabaseHas('game_scores', [
            'game_id' => $game->id,
            'user_id' => $user->id,
            'score' => 15,
        ]);
    }
}
