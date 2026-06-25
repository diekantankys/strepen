<div>
    <div wire:ignore id="game-container"
        style="display: flex; justify-content: center; align-items: center; width: 100%; height: 85vh; padding: 10px; box-sizing: border-box;">
        {{-- CSS properties are fixed at 100%, let JavaScript adjust the canvas attributes --}}
        <canvas id="flappy-bird-canvas"
            data-text-start="@lang('games.flappy_bird_press_to_start')"
            style="width: 100%; height: 100%; max-width: 100%; max-height: 100%; object-fit: contain; touch-action: none;">
        </canvas>
    </div>

    {{-- Game over modal --}}
    <div class="modal @if($showModal) is-active @endif">
        <div class="modal-background" wire:click="playAgain"></div>
        <div class="modal-card" style="width: min(92vw, 800px);">
            <header class="modal-card-head">
                <p class="modal-card-title">@lang('games.flappy_bird_game_over')</p>
                <button class="delete" wire:click="playAgain"></button>
            </header>
            <section class="modal-card-body">
                <div class="columns">
                    {{-- Left: score + user chooser (kiosk only) --}}
                    <div class="column is-5">
                        <div class="has-text-centered" style="padding: 1rem 0 1.5rem;">
                            <p class="heading" style="font-size: 0.9rem; letter-spacing: 0.1em;">@lang('games.flappy_bird_your_score')</p>
                            <p style="font-size: 5.5rem; font-weight: 800; line-height: 1; color: #f8d030; text-shadow: 0 2px 12px rgba(0,0,0,0.25);">{{ $score }}</p>
                        </div>

                        @if ($isKiosk)
                            <livewire:components.user-chooser name="game_user" :userId="$selectedUserId" :anonymous="true" />
                        @endif
                    </div>

                    {{-- Divider --}}
                    <div class="column is-1 is-hidden-mobile" style="display: flex; align-items: stretch; justify-content: center;">
                        <div style="width: 1px; background: #dbdbdb;"></div>
                    </div>

                    {{-- Right: monthly leaderboard --}}
                    <div class="column is-6 w-100">
                        <h3 class="title is-6 mb-3 has-text-centered">
                            @lang('games.flappy_bird_monthly_leaderboard')
                        </h3>

                        @if ($leaderboard->isEmpty())
                            <p class="has-text-grey has-text-centered" style="padding: 2rem 0;">
                                <i>@lang('games.flappy_bird_no_scores_yet')</i>
                            </p>
                        @else
                            <table class="table is-fullwidth is-striped is-narrow">
                                <thead>
                                    <tr>
                                        <th class="medal-column">#</th>
                                        <th>@lang('games.flappy_bird_player')</th>
                                        <th class="has-text-right">@lang('games.flappy_bird_score_label')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($leaderboard as $i => $entry)
                                        <tr>
                                            <td>
                                                @if ($i === 0)
                                                    <div class="medal is-gold">1</div>
                                                @elseif ($i === 1)
                                                    <div class="medal is-silver">2</div>
                                                @elseif ($i === 2)
                                                    <div class="medal is-bronze">3</div>
                                                @else
                                                    <div class="medal">{{ $i + 1 }}</div>
                                                @endif
                                            </td>
                                            <td style="vertical-align: middle;">
                                                @if ($entry->user)
                                                    {{ $entry->user->name }}
                                                @else
                                                    @lang('components.user_chooser.anonymous')
                                                @endif
                                            </td>
                                            <td class="has-text-right"><strong>{{ $entry->score }}</strong></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </section>
            <footer class="modal-card-foot" style="justify-content: flex-end;">
                <button class="button is-success" wire:click="playAgain" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="playAgain">@lang('games.flappy_bird_play_again')</span>
                    <span wire:loading wire:target="playAgain">@lang('games.flappy_bird_saving')</span>
                </button>
            </footer>
        </div>
    </div>

    @once
    <script src="/js/games/flappy-bird.js?v={{ config('app.version') }}"></script>
    @endonce
</div>
