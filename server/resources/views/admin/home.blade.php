@component('layouts.app')
    @slot('title', __('admin/home.title'))
    <div class="container">
        <h1 class="title">@lang('admin/home.header')</h1>

        <div class="buttons">
            @if (Auth::user()->admin)
                <a class="button" href="{{ route('admin.settings') }}" wire:navigate>@lang('admin/home.settings')</a>
                <a class="button" href="{{ route('admin.api_keys.crud') }}" wire:navigate>@lang('admin/home.api_keys')</a>
            @endif
            <a class="button" href="{{ route('admin.users.crud') }}" wire:navigate>@lang('admin/home.users')</a>
            <a class="button" href="{{ route('admin.posts.crud') }}" wire:navigate>@lang('admin/home.posts')</a>
            <a class="button" href="{{ route('admin.products.crud') }}" wire:navigate>@lang('admin/home.products')</a>
            <a class="button" href="{{ route('admin.inventories.crud') }}" wire:navigate>@lang('admin/home.inventories')</a>
            <a class="button" href="{{ route('admin.transactions.crud') }}" wire:navigate>@lang('admin/home.transactions')</a>
        </div>
    </div>
@endcomponent
