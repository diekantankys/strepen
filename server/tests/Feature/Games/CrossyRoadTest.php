<?php

namespace Tests\Feature\Games;

use App\Http\Livewire\Games\CrossyRoad;
use App\Models\Game;
use App\Models\GameScore;
use App\Models\User;
use Livewire;
use Tests\TestCase;

class CrossyRoadTest extends TestCase
{
    public function test_game_over_event_opens_modal_with_score()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Livewire::test(CrossyRoad::class)
            ->dispatch('gameOver', 7)
            ->assertSet('score', 7)
            ->assertSet('showModal', true)
            ->assertSee($user->name);
        $this->assertDatabaseHas('game_scores', [
            'game_id' => Game::where('slug', 'crossy-road')->value('id'),
            'user_id' => $user->id,
            'score' => 7,
        ]);
    }

    public function test_play_again_dispatches_restart()
    {
        $user = User::factory()->create();
        Livewire::test(CrossyRoad::class)
            ->set('selectedUserId', $user->id)
            ->dispatch('gameOver', 12)
            ->call('playAgain')
            ->assertSet('showModal', false)
            ->assertDispatched('game-restart');
        $this->assertDatabaseHas('game_scores', [
            'game_id' => Game::where('slug', 'crossy-road')->value('id'),
            'user_id' => $user->id,
            'score' => 12,
        ]);
    }

    public function test_kiosk_user_selection_moves_score_into_updated_leaderboard()
    {
        $user = User::factory()->create();
        Livewire::test(CrossyRoad::class)
            ->dispatch('gameOver', 20)
            ->assertSee(__('components.user_chooser.anonymous'))
            ->dispatch('inputValue', 'game_user', $user->id)
            ->assertSet('selectedUserId', $user->id)
            ->assertSee($user->name);
        $this->assertDatabaseMissing('game_scores', [
            'game_id' => Game::where('slug', 'crossy-road')->value('id'),
            'user_id' => null,
            'score' => 20,
        ]);
        $this->assertDatabaseHas('game_scores', [
            'game_id' => Game::where('slug', 'crossy-road')->value('id'),
            'user_id' => $user->id,
            'score' => 20,
        ]);
    }

    public function test_anonymous_high_score_adds_a_new_entry()
    {
        $game = Game::where('slug', 'crossy-road')->firstOrFail();
        GameScore::create(['game_id' => $game->id, 'user_id' => null, 'score' => 10]);
        Livewire::test(CrossyRoad::class)->dispatch('gameOver', 15);
        $this->assertSame(2, GameScore::where('game_id', $game->id)->whereNull('user_id')->count());
        $this->assertDatabaseHas('game_scores', [
            'game_id' => $game->id,
            'user_id' => null,
            'score' => 10,
        ]);
        $this->assertDatabaseHas('game_scores', [
            'game_id' => $game->id,
            'user_id' => null,
            'score' => 15,
        ]);
    }

    public function test_real_user_monthly_score_is_updated_in_place()
    {
        $game = Game::where('slug', 'crossy-road')->firstOrFail();
        $user = User::factory()->create();
        $score = GameScore::create(['game_id' => $game->id, 'user_id' => $user->id, 'score' => 10]);
        GameScore::create(['game_id' => $game->id, 'user_id' => $user->id, 'score' => 8]);
        Livewire::test(CrossyRoad::class)
            ->set('selectedUserId', $user->id)
            ->dispatch('gameOver', 15)
            ->call('playAgain');
        $this->assertSame(1, GameScore::where('game_id', $game->id)->where('user_id', $user->id)->count());
        $this->assertDatabaseMissing('game_scores', [
            'game_id' => $game->id,
            'user_id' => $user->id,
            'score' => 10,
        ]);
        $this->assertDatabaseHas('game_scores', [
            'id' => $score->id,
            'game_id' => $game->id,
            'user_id' => $user->id,
            'score' => 15,
        ]);
    }
}
