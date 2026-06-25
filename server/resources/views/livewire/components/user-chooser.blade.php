@if (!$inline)
<div class="field">
    <label class="label" for="userName">@lang('components.user_chooser.user')</label>
@endif
    <div @class(['dropdown', 'is-active' => $isOpen, 'control']) style="width: 100%;">
        <div class="dropdown-trigger control has-icons-left" style="width: 100%;">
            <input id="user-chooser-input-{{ $htmlInputId }}" @class(['input', 'is-danger' => !$valid]) type="text"
                placeholder="@lang($relationship ? 'components.user_chooser.search_by_user' : 'components.user_chooser.search_user')"
                id="userName" autocomplete="off" wire:model="userName" wire:focus="$set('isOpen', true)">
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
                    <a wire:click.prevent="selectUser({{ $user->id }})" class="dropdown-item" wire:key="{{ $user->id }}">
                        <div class="image is-small is-round is-inline" style="background-image: url(/storage/avatars/{{ $user->avatar ?? App\Models\Setting::get('default_user_avatar') }});"></div>
                        {!! $userName != '' ? str_replace(' ', '&nbsp;', preg_replace('#(' . preg_quote($userName) . ')#i', '<b>$1</b>', $user->name)) : $user->name !!}
                    </a>
                @empty
                    <div class="dropdown-item"><i>@lang('components.user_chooser.empty')</i></div>
                @endforelse
            </div>
        </div>

        <script>
            (function () {
                const userChooserInput = document.getElementById('user-chooser-input-{{ $htmlInputId }}');
                const userChooserDropdown = document.getElementById('user-chooser-dropdown-{{ $htmlInputId }}');
                let selectedItem = -1;
                userChooserInput.addEventListener('keydown', event => {
                    if (event.key == 'Enter') {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    const items = userChooserDropdown.children;
                    if (event.key == 'Enter' || event.key == 'Tab') {
                        event.preventDefault();
                        if (selectedItem != -1) {
                            @this.selectUser(items[selectedItem].getAttribute('wire:key'));
                        } else {
                            @this.selectFirstUser();
                        }
                    }
                    else if (event.key == 'ArrowUp') {
                        event.preventDefault();
                        if (selectedItem != -1) items[selectedItem].classList.remove('is-active');
                        if (selectedItem > -1) {
                            selectedItem--;
                        } else {
                            selectedItem = items.length - 1;
                        }
                        if (selectedItem != -1) items[selectedItem].classList.add('is-active');
                    }
                    else if (event.key == 'ArrowDown') {
                        event.preventDefault();
                        if (selectedItem != -1) items[selectedItem].classList.remove('is-active');
                        if (selectedItem < items.length - 1) {
                            selectedItem++;
                        } else {
                            selectedItem = -1;
                        }
                        if (selectedItem != -1) items[selectedItem].classList.add('is-active');
                    }
                    else {
                        selectedItem = -1;
                    }
                });
                userChooserInput.addEventListener('blur', () => {
                    setTimeout(() => {
                        @this.$set('isOpen', false);
                        selectedItem = -1;
                    }, 100);
                });
            })();
        </script>
    </div>
@if (!$inline)
    @if (!$valid) <p class="help is-danger">@lang('components.user_chooser.empty_error')</p> @endif
</div>
@endif
