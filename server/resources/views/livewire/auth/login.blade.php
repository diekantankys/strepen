<form class="container" wire:submit.prevent="login">
    <h2 class="title">@lang('auth.login.header')</h2>

    <div class="box">
        <div class="field">
            <label class="label" for="email">@lang('auth.login.email')</label>
            <div class="control">
                <input class="input @error('email') is-danger @enderror" type="email" id="email"
                    wire:model="email" tabindex="1" autofocus required>
            </div>
            @error('email') <p class="help is-danger">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label class="label" for="password">@lang('auth.login.password') (<a href="{{ route('auth.forgot_password') }}" tabindex="4" wire:navigate>@lang('auth.login.forgot')</a>)</label>
            <div class="control">
                <input class="input @error('password') is-danger @enderror" type="password" id="password"
                    wire:model="password" tabindex="2" required>
            </div>
            @error('password') @if ($message != 'null') <p class="help is-danger">{{ $message }}</p> @endif @enderror
        </div>

        <div class="field">
            <div class="control">
                <button class="button is-link" tabindex="3" type="submit">@lang('auth.login.login')</button>
            </div>
        </div>
    </div>
</form>
