<div style="margin-top: -3rem;">
    <div wire:ignore id="game-container"
        data-text-start="@lang('games.crossy_road_press_to_start')"
        style="position: relative; display: flex; justify-content: center; align-items: center; width: 100%; height: calc(100dvh - 3.25rem); overflow: hidden; touch-action: none;">

        {{-- Score overlay --}}
        <div id="crossy-road-score"
            style="position: absolute; top: 16px; left: 50%; transform: translateX(-50%);
                   background: rgba(0,0,0,0.45); color: white;
                   font-size: 4rem; font-weight: 800;
                   padding: 0.15rem 1.1rem; border-radius: 10px;
                   pointer-events: none; z-index: 10;
                   display: none; white-space: nowrap;">
            0
        </div>

        {{-- Idle countdown timer (top-right) --}}
        <div id="crossy-road-timer"
            style="position: absolute; top: 16px; right: 16px;
                   background: rgba(0,0,0,0.55); color: white;
                   font-size: 2.5rem; font-weight: 800;
                   padding: 0.2rem 0.9rem; border-radius: 10px;
                   pointer-events: none; z-index: 10;
                   display: none; min-width: 3.2rem; text-align: center;">
            10
        </div>

        {{-- Start overlay --}}
        <div id="crossy-road-start"
            style="position: absolute; z-index: 10; background: rgba(0,0,0,0.45);
                   color: white; font-weight: bold; padding: 0.6rem 1.2rem;
                   border-radius: 8px; pointer-events: none;">
            @lang('games.crossy_road_press_to_start')
        </div>
    </div>

    <livewire:games.game-over-modal
        :showModal="$showModal"
        :score="$score"
        :isKiosk="$isKiosk"
        :selectedUserId="$selectedUserId"
        :leaderboard="$leaderboard"
        translationPrefix="crossy_road"
    />

    @once
    <script src="/js/three.min.js?v={{ config('app.version') }}"></script>
    <script src="/js/games/crossy-road.js?v={{ config('app.version') }}"></script>
    @endonce
</div>
