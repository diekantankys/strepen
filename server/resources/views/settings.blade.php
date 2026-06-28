@component('layouts.app')
    @slot('title', __('settings.title'))
    <div class="container">
        <h1 class="title">@lang('settings.header')</h1>

        <livewire:settings.change-details />

        <div class="columns">
            <div class="column is-half">
                <livewire:settings.change-notifications />
            </div>

            <div class="column is-half">
                <livewire:settings.change-password />
            </div>
        </div>

        <div class="columns">
            <div class="column is-half">
                <livewire:settings.change-avatar />
            </div>

            <div class="column is-half">
                <livewire:settings.change-thanks />
            </div>
        </div>
    </div>
@endcomponent
