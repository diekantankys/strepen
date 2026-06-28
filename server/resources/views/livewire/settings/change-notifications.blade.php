<div>
    @if ($isChanged)
        <div class="notification is-success">
            <button class="delete" wire:click="$set('isChanged', false)"></button>
            <p>@lang('settings.change_notifications.success_message')</p>
        </div>
    @endif

    <form class="box" wire:submit.prevent="changeNotifications">
        <h2 class="title is-4">@lang('settings.change_notifications.header')</h2>

        <div class="field">
            <label class="checkbox">
                <input type="checkbox" wire:model="user.notify_new_posts">
                @lang('settings.change_notifications.notify_new_posts')
            </label>
        </div>

        <div class="field">
            <label class="checkbox">
                <input type="checkbox" wire:model="user.notify_low_balance">
                @lang('settings.change_notifications.notify_low_balance')
            </label>
        </div>

        <div class="field">
            <label class="checkbox">
                <input type="checkbox" wire:model="user.notify_new_deposits">
                @lang('settings.change_notifications.notify_new_deposits')
            </label>
        </div>

        <div class="field">
            <label class="checkbox">
                <input type="checkbox" wire:model="user.notify_new_transactions">
                @lang('settings.change_notifications.notify_new_transactions')
            </label>
        </div>

        <hr>

        <div class="field">
            <label class="checkbox">
                <input type="checkbox" wire:model="user.notify_by_email">
                @lang('settings.change_notifications.notify_by_email')
            </label>
        </div>

        <div class="field">
            <div class="control">
                <button class="button is-link" type="submit">@lang('settings.change_notifications.button')</button>
            </div>
        </div>
    </form>
</div>
