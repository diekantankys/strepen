<div class="navbar is-fixed-top">
    <div class="container">
        <div class="navbar-brand">
            <a @class(['navbar-item', 'is-active' => Route::is('home') || Route::is('posts.show'), 'has-text-weight-bold']) href="{{ route('home') }}" wire:navigate>
                <svg xmlns="http://www.w3.org/2000/svg" class="image is-small" viewBox="0 0 24 24">
                    <path d="M9.5 3C7.56 3 5.85 4.24 5.23 6.08C3.36 6.44 2 8.09 2 10C2 12.21 3.79 14 6 14V22H17V20H20C20.55 20 21 19.55 21 19V11C21 10.45 20.55 10 20 10H18V8C18 5.79 16.21 4 14 4H12.32C11.5 3.35 10.53 3 9.5 3M9.5 5C10.29 5 11.03 5.37 11.5 6H14C15.11 6 16 6.9 16 8H12C10 8 9.32 9.13 8.5 10.63C7.68 12.13 6 12 6 12C4.89 12 4 11.11 4 10C4 8.9 4.89 8 6 8H7V7.5C7 6.12 8.12 5 9.5 5M17 12H19V18H17Z" />
                </svg>
                {{ config('app.name') }}
            </a>
            <a class="navbar-burger burger"><span></span><span></span><span></span></a>
        </div>
        <div class="navbar-menu">
            @auth
                @if (Auth::id() == 1)
                    <div class="navbar-start">
                        <a @class(['navbar-item', 'is-active' => Route::is('transactions.create')]) href="{{ route('transactions.create') }}" wire:navigate>@lang('layout.navbar.transactions_create')</a>
                        @if (App\Models\Setting::get('leaderboards_enabled') == 'true')
                            <a @class(['navbar-item', 'is-active' => Route::is('leaderboards')]) href="{{ route('leaderboards') }}" wire:navigate>@lang('layout.navbar.leaderboards')</a>
                        @endif
                        <a @class(['navbar-item', 'is-active' => Route::is('games.*')]) href="{{ route('games.index') }}">@lang('layout.navbar.games')</a>
                    </div>

                    <div class="navbar-end">
                        <a @class(['navbar-item', 'is-active' => Route::is('apps')]) href="{{ route('apps') }}" wire:navigate>
                            <svg xmlns="http://www.w3.org/2000/svg" class="is-hidden-touch" style="width: 1.5rem; height: 1.5rem;" viewBox="0 0 24 24">
                                <path d="M17,1H7A2,2 0 0,0 5,3V21A2,2 0 0,0 7,23H17A2,2 0 0,0 19,21V3A2,2 0 0,0 17,1M17,19H7V5H17V19M16,13H13V8H11V13H8L12,17L16,13Z" />
                            </svg>
                            <span class="is-hidden-desktop">@lang('layout.navbar.apps')</span>
                        </a>

                        <div class="navbar-item">
                            <form class="buttons" method="POST" action="{{ route('auth.logout') }}">
                                @csrf
                                <button type="submit" class="button">@lang('layout.navbar.logout')</button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="navbar-start">
                        <a @class(['navbar-item', 'is-active' => Route::is('transactions.create')]) href="{{ route('transactions.create') }}" wire:navigate>@lang('layout.navbar.transactions_create')</a>
                        <a @class(['navbar-item', 'is-active' => Route::is('transactions.history')]) href="{{ route('transactions.history') }}" wire:navigate>@lang('layout.navbar.transactions_history')</a>
                        @if (App\Models\Setting::get('leaderboards_enabled') == 'true' || Auth::user()->manager)
                            <a @class(['navbar-item', 'is-active' => Route::is('leaderboards')]) href="{{ route('leaderboards') }}" wire:navigate>@lang('layout.navbar.leaderboards')</a>
                        @endif
                        <a @class(['navbar-item', 'is-active' => Route::is('games.*')]) href="{{ route('games.index') }}">@lang('layout.navbar.games')</a>

                        @if (Auth::user()->manager)
                            <div class="navbar-item has-dropdown is-hoverable">
                                <a @class(['navbar-link', 'is-active' => Route::is('admin.*'), 'is-arrowless'])
                                    href="{{ route('admin.home') }}" wire:navigate>@lang('layout.navbar.admin_home')</a>
                                <div class="navbar-dropdown">
                                    @if (Auth::user()->admin)
                                        <a @class(['navbar-item', 'is-active' => Route::is('admin.settings')]) href="{{ route('admin.settings') }}" wire:navigate>@lang('layout.navbar.admin_settings')</a>
                                        <a @class(['navbar-item', 'is-active' => Route::is('admin.api_keys.crud')]) href="{{ route('admin.api_keys.crud') }}" wire:navigate>@lang('layout.navbar.admin_api_keys')</a>
                                    @endif
                                    <a @class(['navbar-item', 'is-active' => Route::is('admin.users.crud')]) href="{{ route('admin.users.crud') }}" wire:navigate>@lang('layout.navbar.admin_users')</a>
                                    <a @class(['navbar-item', 'is-active' => Route::is('admin.posts.crud')]) href="{{ route('admin.posts.crud') }}" wire:navigate>@lang('layout.navbar.admin_posts')</a>
                                    <a @class(['navbar-item', 'is-active' => Route::is('admin.post_comments.crud')]) href="{{ route('admin.post_comments.crud') }}" wire:navigate>@lang('layout.navbar.admin_post_comments')</a>
                                    <a @class(['navbar-item', 'is-active' => Route::is('admin.products.crud')]) href="{{ route('admin.products.crud') }}" wire:navigate>@lang('layout.navbar.admin_products')</a>
                                    <a @class(['navbar-item', 'is-active' => Route::is('admin.inventories.crud')]) href="{{ route('admin.inventories.crud') }}" wire:navigate>@lang('layout.navbar.admin_inventories')</a>
                                    <a @class(['navbar-item', 'is-active' => Route::is('admin.transactions.crud')]) href="{{ route('admin.transactions.crud') }}" wire:navigate>@lang('layout.navbar.admin_transactions')</a>
                                    <a @class(['navbar-item', 'is-active' => Route::is('admin.games.*')]) href="{{ route('admin.games.index') }}" wire:navigate>@lang('layout.navbar.admin_games')</a>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="navbar-end">
                        <a @class(['navbar-item', 'is-active' => Route::is('apps')]) href="{{ route('apps') }}" wire:navigate>
                            <svg xmlns="http://www.w3.org/2000/svg" class="is-hidden-touch" style="width: 1.5rem; height: 1.5rem;" viewBox="0 0 24 24">
                                <path d="M17,1H7A2,2 0 0,0 5,3V21A2,2 0 0,0 7,23H17A2,2 0 0,0 19,21V3A2,2 0 0,0 17,1M17,19H7V5H17V19M16,13H13V8H11V13H8L12,17L16,13Z" />
                            </svg>
                            <span class="is-hidden-desktop">@lang('layout.navbar.apps')</span>
                        </a>

                        <livewire:components.notifications />

                        <a @class(['navbar-item', 'is-active' => Route::is('balance')]) href="{{ route('balance') }}" style="display: flex; align-items: center;" wire:navigate>
                            <div class="image is-medium is-round" style="background-image: url(/storage/avatars/{{ Auth::user()->avatar ?? App\Models\Setting::get('default_user_avatar') }});"></div>
                            <span class="mr-3">{{ Auth::user()->name }}</span>
                            <x-money-format :money="Auth::user()->balance" />
                        </a>

                        <div class="navbar-item">
                            <div class="buttons">
                                @if (Auth::user()->manager || in_array(Request::ip(), array_map('trim', explode(',', App\Models\Setting::get('kiosk_ip_whitelist')))))
                                    <a class="button" href="{{ route('admin.kiosk') }}">@lang('layout.navbar.admin_kiosk')</a>
                                @endif
                                <a class="button is-link" href="{{ route('settings') }}" wire:navigate>@lang('layout.navbar.settings')</a>
                                <form method="POST" action="{{ route('auth.logout') }}">
                                    @csrf
                                    <button type="submit" class="button">@lang('layout.navbar.logout')</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <div class="navbar-end">
                    <a @class(['navbar-item', 'is-active' => Route::is('apps')]) href="{{ route('apps') }}" wire:navigate>
                        <svg xmlns="http://www.w3.org/2000/svg" class="is-hidden-touch" style="width: 1.5rem; height: 1.5rem;" viewBox="0 0 24 24">
                            <path d="M17,1H7A2,2 0 0,0 5,3V21A2,2 0 0,0 7,23H17A2,2 0 0,0 19,21V3A2,2 0 0,0 17,1M17,19H7V5H17V19M16,13H13V8H11V13H8L12,17L16,13Z" />
                        </svg>
                        <span class="is-hidden-desktop">@lang('layout.navbar.apps')</span>
                    </a>

                    <div class="navbar-item">
                        <div class="buttons">
                            @if (in_array(Request::ip(), array_map('trim', explode(',', App\Models\Setting::get('kiosk_ip_whitelist')))))
                                <a class="button" href="{{ route('admin.kiosk') }}">@lang('layout.navbar.admin_kiosk')</a>
                            @endif
                            <a class="button is-link" href="{{ route('auth.login') }}" wire:navigate>@lang('layout.navbar.login')</a>
                        </div>
                    </div>
                </div>
            @endauth
        </div>
    </div>
</div>
