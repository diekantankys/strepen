<div @class(['control' => $inline]) style="width: 100%;">
@if (!$inline)
<div class="field">
    <label class="label" for="userName">@lang('components.user_chooser.user')</label>
@endif
    <div @class(['dropdown', 'is-active' => $isOpen, 'control' => !$inline]) style="width: 100%;">
        <div class="dropdown-trigger control has-icons-left" style="width: 100%;">
            <input id="user-chooser-input-{{ $htmlInputId }}" @class(['input', 'is-danger' => !$valid || $invalid]) type="text"
                placeholder="@lang($relationship ? 'components.user_chooser.search_by_user' : 'components.user_chooser.search_user')"
                autocomplete="off" wire:model.live.debounce.150ms="userName" wire:focus="$set('isOpen', true)"
                data-chooser-input data-chooser-type="user" data-chooser-dropdown="user-chooser-dropdown-{{ $htmlInputId }}">
            <span class="icon is-small is-left">
                <div class="image is-small is-round" style="background-image: url(/storage/avatars/{{ $user != null && $user->avatar != null ? $user->avatar : App\Models\Setting::get('default_user_avatar') }});"></div>
            </span>
        </div>
        <div class="dropdown-menu" style="width: 100%;">
            <div id="user-chooser-dropdown-{{ $htmlInputId }}" class="dropdown-content">
                @if ($anonymous)
                    <a wire:click.prevent="selectAnonymous()" class="dropdown-item">
                        <div class="image is-small is-round is-inline" style="background-image: url(/storage/avatars/{{ App\Models\Setting::get('default_user_avatar') }});"></div>
                        @lang('components.user_chooser.anonymous')
                    </a>
                @endif
                @forelse ($filteredUsers as $user)
                    <a wire:click.prevent="selectUser({{ $user->id }})" class="dropdown-item" wire:key="{{ $user->id }}" data-chooser-option data-chooser-value="{{ $user->id }}">
                        <div class="image is-small is-round is-inline" style="background-image: url(/storage/avatars/{{ $user->avatar ?? App\Models\Setting::get('default_user_avatar') }});"></div>
                        {!! $userName != '' ? str_replace(' ', '&nbsp;', preg_replace('#(' . preg_quote($userName) . ')#i', '<b>$1</b>', $user->name)) : $user->name !!}
                    </a>
                @empty
                    <div class="dropdown-item"><i>@lang('components.user_chooser.empty')</i></div>
                @endforelse
            </div>
        </div>

    </div>
@if (!$inline)
    @if (!$valid || $invalid) <p class="help is-danger">@lang('components.user_chooser.empty_error')</p> @endif
</div>
@endif
</div>
