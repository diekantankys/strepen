<div>
    <div wire:ignore id="game-container"
        style="display: flex; justify-content: center; align-items: center; width: 100%; height: 85vh; padding: 10px; box-sizing: border-box;">
        <canvas id="wanted-canvas"
            data-faces="{{ $faces->toJson() }}"
            data-text-start="@lang('games.wanted_press_to_start')"
            data-text-no-faces="@lang('games.wanted_no_faces')"
            data-text-wanted="@lang('games.wanted_poster_label')"
            data-text-score="@lang('games.wanted_score_label')"
            data-text-lives="@lang('games.wanted_lives_label')"
            data-text-time="@lang('games.wanted_time_label')"
            style="width: 100%; height: 100%; max-width: 100%; max-height: 100%; object-fit: contain; touch-action: none;">
        </canvas>
    </div>

    <livewire:games.game-over-modal
        :showModal="$showModal"
        :score="$score"
        :isKiosk="$isKiosk"
        :selectedUserId="$selectedUserId"
        :leaderboard="$leaderboard"
        translationPrefix="wanted"
    />

    @once
    <script src="/js/games/wanted.js?v={{ config('app.version') }}"></script>
    @endonce
</div>
