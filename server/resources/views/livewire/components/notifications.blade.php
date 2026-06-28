<div class="navbar-item has-dropdown is-hoverable">
    <a @class(['navbar-link', 'is-active' => Route::is('notifications'), 'is-arrowless', 'is-hidden-touch']) href="{{ route('notifications') }}" wire:navigate>
        <svg xmlns="http://www.w3.org/2000/svg" style="width: 1.5rem; height: 1.5rem;" viewBox="0 0 24 24">
            <path d="@if ($notifications->count() > 0) M21 6.5C21 8.43 19.43 10 17.5 10S14 8.43 14 6.5 15.57 3 17.5 3 21 4.57 21 6.5M19 11.79C18.5 11.92 18 12 17.5 12C14.47 12 12 9.53 12 6.5C12 5.03 12.58 3.7 13.5 2.71C13.15 2.28 12.61 2 12 2C10.9 2 10 2.9 10 4V4.29C7.03 5.17 5 7.9 5 11V17L3 19V20H21V19L19 17V11.79M12 23C13.11 23 14 22.11 14 21H10C10 22.11 10.9 23 12 23Z
                    @else M21,19V20H3V19L5,17V11C5,7.9 7.03,5.17 10,4.29C10,4.19 10,4.1 10,4A2,2 0 0,1 12,2A2,2 0 0,1 14,4C14,4.1 14,4.19 14,4.29C16.97,5.17 19,7.9 19,11V17L21,19M14,21A2,2 0 0,1 12,23A2,2 0 0,1 10,21 @endif" />
        </svg>
    </a>
    <div class="navbar-dropdown">
        @forelse ($notifications as $notification)
            @if ($notification->type == 'App\Notifications\NewDeposit')
                <a class="navbar-item is-notification has-text-centered" href="{{ route('transactions.history') }}" wire:navigate wire:key="{{ $notification->id }}"
                    style="flex-direction: column; padding: .75rem 1rem;">
                    @php
                        $transaction = App\Models\Transaction::withTrashed()->find($notification->data['transaction_id']);
                    @endphp
                    <h1 class="title is-6 mb-1" style="width: 100%; line-height: .75rem;">
                        @lang('components.notifications.new_deposit_header')
                        <button type="button" class="delete is-small is-pulled-right" wire:click.stop.prevent="readNotification('{{ $notification->id }}')"></button>
                    </h1>
                    <p style="white-space: normal;">@lang('components.notifications.new_deposit_start') <x-money-format :money="$transaction->price" />
                        @lang('components.notifications.new_deposit_end') {{ $transaction->created_at->format('Y-m-d H:i') }}</p>
                </a>
            @endif

            @if ($notification->type == 'App\Notifications\NewPost')
                @php
                    $post = App\Models\Post::withTrashed()->find($notification->data['post_id']);
                @endphp
                <a class="navbar-item is-notification has-text-centered" href="{{ route('posts.show', $post) }}" wire:navigate wire:key="{{ $notification->id }}"
                    style="flex-direction: column; padding: .75rem 1rem;">
                    <h1 class="title is-6 mb-1" style="width: 100%; line-height: .75rem;">
                        @lang('components.notifications.new_post_header')
                        <button type="button" class="delete is-small is-pulled-right" wire:click.stop.prevent="readNotification('{{ $notification->id }}')"></button>
                    </h1>
                    <p style="white-space: normal;">@lang('components.notifications.new_post_text', ['post.title' => $post->title, 'post.created_at' => $post->created_at->format('Y-m-d H:i')])</p>
                </a>
            @endif

            @if ($notification->type == 'App\Notifications\NewTransaction')
                <a class="navbar-item is-notification has-text-centered" href="{{ route('transactions.history') }}" wire:navigate wire:key="{{ $notification->id }}"
                    style="flex-direction: column; padding: .75rem 1rem;">
                    @php
                        $transaction = App\Models\Transaction::withTrashed()->find($notification->data['transaction_id']);
                    @endphp
                    <h1 class="title is-6 mb-1" style="width: 100%; line-height: .75rem;">
                        @lang('components.notifications.new_transaction_header')
                        <button type="button" class="delete is-small is-pulled-right" wire:click.stop.prevent="readNotification('{{ $notification->id }}')"></button>
                    </h1>
                    <p style="white-space: normal;">@lang('components.notifications.new_transaction_start') <x-money-format :money="$transaction->price" />
                        @lang('components.notifications.new_transaction_end') {{ $transaction->created_at->format('Y-m-d H:i') }}</p>
                </a>
            @endif

            @if ($notification->type == 'App\Notifications\LowBalance')
                <a class="navbar-item is-notification has-text-centered" href="{{ route('balance') }}" wire:navigate wire:key="{{ $notification->id }}"
                    style="flex-direction: column; padding: .75rem 1rem;">
                    <h1 class="title is-6 mb-1" style="width: 100%; line-height: .75rem;">
                        @lang('components.notifications.low_balance_header')
                        <button type="button" class="delete is-small is-pulled-right" wire:click.stop.prevent="readNotification('{{ $notification->id }}')"></button>
                    </h1>
                    <p style="white-space: normal;">@lang('components.notifications.low_balance_start') <x-money-format :money="$notification->data['balance']" />
                        @lang('components.notifications.low_balance_end')</p>
                </a>
            @endif
        @empty
            <div class="navbar-item"><i>@lang('components.notifications.empty')</i></div>
        @endforelse
    </div>
</div>
