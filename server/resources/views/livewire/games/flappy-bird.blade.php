<div style="margin-top: -3rem;">
    <div wire:ignore id="game-container"
        style="display: flex; justify-content: center; align-items: center; width: 100%; height: calc(100dvh - 3.25rem); padding: 4px; box-sizing: border-box;">
        {{-- CSS properties are fixed at 100%, let JavaScript adjust the canvas attributes --}}
        <canvas id="flappy-bird-canvas"
            data-text-start="@lang('games.flappy_bird_press_to_start')"
            style="width: 100%; height: 100%; max-width: 100%; max-height: 100%; object-fit: contain; touch-action: none;">
        </canvas>
    </div>

    <livewire:games.game-over-modal
        :showModal="$showModal"
        :score="$score"
        :isKiosk="$isKiosk"
        :selectedUserId="$selectedUserId"
        :leaderboard="$leaderboard"
        translationPrefix="flappy_bird"
    />

    @once
    <script src="/js/games/flappy-bird.js?v={{ config('app.version') }}"></script>
    @endonce
</div>
