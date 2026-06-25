<div class="container">
    <h2 class="title">@lang('auth.reset_password.header')</h2>

    @if ($isReset)
        <div class="notification is-success">
            <button class="delete" wire:click="$set('isReset', false)"></button>
            <p>@lang('auth.reset_password.success_message')</p>
        </div>
    @endif

    @if ($isError)
        <div class="notification is-danger">
            <button class="delete" wire:click="$set('isError', false)"></button>
            <p>@lang('auth.reset_password.error_message')</p>
        </div>
    @endif

    <form class="box" wire:submit.prevent="resetPassword">
        <div class="field">
            <label class="label" for="password">@lang('auth.reset_password.password')</label>
            <div class="control">
                <input class="input @error('password') is-danger @enderror" type="password" id="password"
                    wire:model="password" required>
            </div>
            @error('password') <p class="help is-danger">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label class="label" for="passwordConfirmation">@lang('auth.reset_password.password_confirmation')</label>
            <div class="control">
                <input class="input @error('passwordConfirmation') is-danger @enderror" type="password" id="passwordConfirmation"
                    wire:model="passwordConfirmation" required>
            </div>
            @error('passwordConfirmation') <p class="help is-danger">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <div class="control">
                <button class="button is-link" type="submit">@lang('auth.reset_password.reset_password')</button>
            </div>
        </div>
    </form>
</div>
