<div class="container">
    <h2 class="title">@lang('auth.forgot_password.header')</h2>

    @if ($isSend)
        <div class="notification is-success">
            <button class="delete" wire:click="$set('isSend', false)"></button>
            <p>@lang('auth.forgot_password.success_message')</p>
        </div>
    @endif

    @if ($isError)
        <div class="notification is-danger">
            <button class="delete" wire:click="$set('isError', false)"></button>
            <p>@lang('auth.forgot_password.error_message')</p>
        </div>
    @endif

    <form class="box" wire:submit.prevent="forgotPassword">
        <div class="field">
            <label class="label" for="email">@lang('auth.forgot_password.email')</label>
            <div class="control">
                <input class="input @error('email') is-danger @enderror" type="email" id="email"
                    wire:model="email" autofocus required>
            </div>
            @error('email') <p class="help is-danger">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <div class="control">
                <button class="button is-link" type="submit">@lang('auth.forgot_password.reset_password')</button>
            </div>
        </div>
    </form>
</div>
