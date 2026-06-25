@component('layouts.app')
    @slot('title', __('admin/games.index.title'))
    <div class="container">
        <h1 class="title">@lang('admin/games.index.header')</h1>

        <div class="columns is-multiline">
            <div class="column is-one-third-desktop is-half-tablet">
                <a href="{{ route('admin.games.wanted') }}" wire:navigate>
                    <div class="card">
                        <div class="card-image">
                            <img src="/images/games/wanted-preview.svg" alt="@lang('games.wanted')" style="height: 220px; width: 100%; object-fit: cover; background: #0a0a1c; border-top-left-radius: 0.25rem; border-top-right-radius: 0.25rem; display: block;">
                        </div>
                        <div class="card-content">
                            <div class="media is-align-items-center">
                                <div class="media-content">
                                    <p class="title is-4 mb-1">@lang('games.wanted')</p>
                                    <p class="subtitle is-6 has-text-grey">@lang('admin/games.index.wanted_description')</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
@endcomponent
