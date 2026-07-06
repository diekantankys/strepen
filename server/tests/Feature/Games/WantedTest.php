<?php

namespace Tests\Feature\Games;

use App\Http\Livewire\Games\Wanted;
use App\Models\Game;
use App\Models\GameScore;
use App\Models\User;
use Livewire;
use Tests\TestCase;

class WantedTest extends TestCase
{
    public function test_game_over_event_opens_modal_and_saves_score()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Wanted::class)
            ->dispatch('gameOver', 7)
            ->assertSet('score', 7)
            ->assertSet('showModal', true);

        $this->assertDatabaseHas('game_scores', [
            'game_id' => Game::where('slug', 'wanted')->value('id'),
            'user_id' => $user->id,
            'score' => 7,
        ]);
    }

    public function test_kiosk_user_selection_moves_score_into_updated_leaderboard()
    {
        $user = User::factory()->create();

        Livewire::test(Wanted::class)
            ->dispatch('gameOver', 20)
            ->dispatch('inputValue', 'game_user', $user->id)
            ->assertSet('selectedUserId', $user->id);

        $this->assertDatabaseMissing('game_scores', [
            'game_id' => Game::where('slug', 'wanted')->value('id'),
            'user_id' => null,
            'score' => 20,
        ]);

        $this->assertDatabaseHas('game_scores', [
            'game_id' => Game::where('slug', 'wanted')->value('id'),
            'user_id' => $user->id,
            'score' => 20,
        ]);
    }

    public function test_play_again_event_dispatches_restart()
    {
        $user = User::factory()->create();

        Livewire::test(Wanted::class)
            ->set('selectedUserId', $user->id)
            ->dispatch('gameOver', 12)
            ->dispatch('game-over-play-again')
            ->assertSet('showModal', false)
            ->assertDispatched('game-restart');
    }

    public function test_real_user_monthly_score_is_updated_in_place()
    {
        $game = Game::where('slug', 'wanted')->firstOrFail();
        $user = User::factory()->create();
        $score = GameScore::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'score' => 10,
        ]);
        GameScore::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'score' => 8,
        ]);

        Livewire::test(Wanted::class)
            ->set('selectedUserId', $user->id)
            ->dispatch('gameOver', 15);

        $this->assertSame(1, GameScore::where('game_id', $game->id)->where('user_id', $user->id)->count());

        $this->assertDatabaseHas('game_scores', [
            'id' => $score->id,
            'game_id' => $game->id,
            'user_id' => $user->id,
            'score' => 15,
        ]);
    }
}
