<div class="container">
    @auth
        <h1 class="title">@lang('home.auth_header', ['user.firstname' => Auth::user()->firstname])</h1>

        <x-search-header :itemName="__('home.posts')">
            <h2 class="title is-4">@lang('home.latest_posts')</h2>

            <x-slot name="sorters">
                <option value="">@lang('home.created_at_desc')</option>
                <option value="created_at">@lang('home.created_at_asc')</option>
                <option value="title">@lang('home.title_asc')</option>
                <option value="title_desc">@lang('home.title_desc')</option>
            </x-slot>

            <x-slot name="filters">
                <livewire:components.user-chooser name="user_filter" :userId="$user_id" inline="true" includeStrepenUser="true" relationship="true" postsRequired="true" wire:key="home-user-filter-chooser" />
            </x-slot>
        </x-search-header>

        @if ($posts->count() > 0)
            {{ $posts->links() }}

            <div class="container is-max-desktop">
                @foreach ($posts as $post)
                    <livewire:posts.item :post="$post" wire:key="post-{{ $post->id }}" />
                @endforeach
            </div>

            {{ $posts->links() }}
        @else
            <p><i>@lang('home.posts_empty')</i></p>
        @endif
    @else
        <div class="content">
            <h1 class="title">@lang('home.guest_header')</h1>

            <p>@lang('home.guest_login_description')</p>

            @if (in_array(Request::ip(), array_map('trim', explode(',', App\Models\Setting::get('kiosk_ip_whitelist')))))
                <p>@lang('home.guest_kiosk_description')</p>
            @endif
        </div>
    @endauth
</div>
