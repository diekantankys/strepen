<div>
    <div wire:ignore id="game-container"
        style="display: flex; justify-content: center; align-items: center; width: 100%; height: 85vh; padding: 10px; box-sizing: border-box;">
        {{-- CSS properties are fixed at 100%, let JavaScript adjust the canvas attributes --}}
        <canvas id="flappy-bird-canvas"
            data-text-start="@lang('games.flappy_bird_press_to_start')"
            style="width: 100%; height: 100%; max-width: 100%; max-height: 100%; object-fit: contain; touch-action: none;">
        </canvas>
    </div>

    <x-game-modal
        :showModal="$showModal"
        :score="$score"
        :isKiosk="$isKiosk"
        :selectedUserId="$selectedUserId"
        :leaderboard="$leaderboard"
        :title="__('games.flappy_bird_game_over')"
        :yourScore="__('games.flappy_bird_your_score')"
        :monthlyLeaderboard="__('games.flappy_bird_monthly_leaderboard')"
        :player="__('games.flappy_bird_player')"
        :scoreLabel="__('games.flappy_bird_score_label')"
        :noScoresYet="__('games.flappy_bird_no_scores_yet')"
        :playAgain="__('games.flappy_bird_play_again')"
        :saving="__('games.flappy_bird_saving')"
    />

    @once
    <script src="/js/games/flappy-bird.js?v={{ config('app.version') }}"></script>
    @endonce
</div>
