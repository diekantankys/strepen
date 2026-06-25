<div class="container">
    <h1 class="title">@lang('games.title')</h1>

    <div class="columns is-multiline">
        <div class="column is-one-third-desktop is-half-tablet">
            <a href="{{ route('games.flappy-bird') }}">
                <div class="card">
                    <div class="card-image">
                        <img src="/images/games/flappy-bird-preview.svg" alt="Flappy Flügel" style="height: 220px; width: 100%; object-fit: cover; background: #1a1a2e; border-top-left-radius: 0.25rem; border-top-right-radius: 0.25rem; display: block;">
                    </div>
                    <div class="card-content">
                        <div class="media is-align-items-center">
                            <div class="media-content">
                                <p class="title is-4 mb-1">Flappy Flügel</p>
                                <p class="subtitle is-6 has-text-grey">@lang('games.flappy_bird_description')</p>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
