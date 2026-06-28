<div class="column is-one-quarter">
    <div class="card">
        <div class="card-image">
            <div class="image is-square" style="background-image: url(/storage/avatars/{{ $user->avatar ?? App\Models\Setting::get('default_user_avatar') }});"></div>

            <div class="card-image-tags">
                @if ($user->role == App\Models\User::ROLE_NORMAL)
                    <span class="tag is-success">{{ Str::upper(__('admin/users.item.role_normal')) }}</span>
                @endif

                @if ($user->role == App\Models\User::ROLE_MANAGER)
                    <span class="tag is-link">{{ Str::upper(__('admin/users.item.role_manager')) }}</span>
                @endif

                @if ($user->role == App\Models\User::ROLE_ADMIN)
                    <span class="tag is-danger">{{ Str::upper(__('admin/users.item.role_admin')) }}</span>
                @endif

                @if (!$user->active)
                    <span class="tag is-warning">{{ Str::upper(__('admin/users.item.inactive')) }}</span>
                @endif
            </div>
        </div>

        <div class="card-content content">
            <h4>{{ $user->name }}</h4>
            <p>@lang('admin/users.item.balance'): <x-money-format :money="$user->balance" /></p>
        </div>

        <div class="card-footer">
            <a class="card-footer-item" wire:click.prevent="$set('isShowing', true)">@lang('admin/users.item.show')</a>
            <a class="card-footer-item" wire:click.prevent="$set('isEditing', true)">@lang('admin/users.item.edit')</a>
            @if (
                (Auth::user()->role == App\Models\User::ROLE_MANAGER && $user->role != App\Models\User::ROLE_ADMIN) ||
                Auth::user()->role == App\Models\User::ROLE_ADMIN
            )
                @if ($user->id != Auth::id())
                    <a class="card-footer-item has-text-danger" wire:click.prevent="hijackUser">@lang('admin/users.item.hijack')</a>
                @endif
                <a class="card-footer-item has-text-danger" wire:click.prevent="$set('isDeleting', true)">@lang('admin/users.item.delete')</a>
            @endif
        </div>
    </div>

    @if ($isShowing)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isShowing', false)"></div>

            <div class="modal-card is-large">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/users.item.show_user')</p>
                    <button type="button" class="delete" wire:click="$set('isShowing', false)"></button>
                </div>

                <div class="modal-card-body content">
                    <div class="columns">
                        <div class="column is-half">
                            <h1 class="title is-spaced is-4">
                                {{ $user->name }}

                                <span class="is-pulled-right is-hidden-mobile">
                                    @if ($user->role == App\Models\User::ROLE_NORMAL)
                                        <span class="tag is-success">{{ Str::upper(__('admin/users.item.role_normal')) }}</span>
                                    @endif

                                    @if ($user->role == App\Models\User::ROLE_MANAGER)
                                        <span class="tag is-link">{{ Str::upper(__('admin/users.item.role_manager')) }}</span>
                                    @endif

                                    @if ($user->role == App\Models\User::ROLE_ADMIN)
                                        <span class="tag is-danger">{{ Str::upper(__('admin/users.item.role_admin')) }}</span>
                                    @endif

                                    @if (!$user->active)
                                        <span class="tag is-warning">{{ Str::upper(__('admin/users.item.inactive')) }}</span>
                                    @endif
                                </span>
                            </h1>

                            <p class="is-display-mobile is-hidden-tablet">
                                @if ($user->role == App\Models\User::ROLE_NORMAL)
                                    <span class="tag is-success">{{ Str::upper(__('admin/users.item.role_normal')) }}</span>
                                @endif

                                @if ($user->role == App\Models\User::ROLE_MANAGER)
                                    <span class="tag is-link">{{ Str::upper(__('admin/users.item.role_manager')) }}</span>
                                @endif

                                @if ($user->role == App\Models\User::ROLE_ADMIN)
                                    <span class="tag is-danger">{{ Str::upper(__('admin/users.item.role_admin')) }}</span>
                                @endif

                                @if (!$user->active)
                                    <span class="tag is-warning">{{ Str::upper(__('admin/users.item.inactive')) }}</span>
                                @endif
                            </p>

                            <div class="columns">
                                <div class="column">
                                    <h2 class="subtitle is-5">@lang('admin/users.item.avatar')</h2>
                                    <div class="box not-fullheight">
                                        <div class="image is-square is-rounded" style="background-image: url(/storage/avatars/{{ $user->avatar ?? App\Models\Setting::get('default_user_avatar') }});"></div>
                                    </div>
                                </div>
                                <div class="column">
                                    <h2 class="subtitle is-5">@lang('admin/users.item.thanks')</h2>
                                    <div class="box not-fullheight">
                                        <div class="image is-square is-rounded" style="background-image: url(/storage/thanks/{{ $user->thanks ?? App\Models\Setting::get('default_user_thanks') }});"></div>
                                    </div>
                                </div>
                            </div>

                            <h2 class="subtitle is-5">@lang('admin/users.item.personal_info')</h2>
                            @if ($user->gender !== null)
                                @if ($user->gender == App\Models\User::GENDER_MALE)
                                    <p>@lang('admin/users.item.gender'): @lang('admin/users.item.gender_male')</p>
                                @endif
                                @if ($user->gender == App\Models\User::GENDER_FEMALE)
                                    <p>@lang('admin/users.item.gender'): @lang('admin/users.item.gender_female')</p>
                                @endif
                                @if ($user->gender == App\Models\User::GENDER_OTHER)
                                    <p>@lang('admin/users.item.gender'): @lang('admin/users.item.gender_other')</p>
                                @endif
                            @else
                                <p>@lang('admin/users.item.gender'): @lang('admin/users.item.gender_unknown')</p>
                            @endif
                            @if ($user->birthday != null)
                                <p>@lang('admin/users.item.birthday'): {{ $user->birthday->format('Y-m-d') }}</p>
                            @else
                                <p>@lang('admin/users.item.birthday'): @lang('admin/users.item.birthday_unknown')</p>
                            @endif
                        </div>

                        <div class="column is-half">
                            <h2 class="subtitle is-5">@lang('admin/users.item.contact_info')</h2>
                            <p>@lang('admin/users.item.email'): <a href="mailto:{{ $user->email }}">{{ $user->email }}</a></p>
                            @if ($user->phone != null)
                                <p>@lang('admin/users.item.phone'): <a href="tel:{{ $user->phone }}">{{ $user->phone }}</a></p>
                            @else
                                <p>@lang('admin/users.item.phone'): @lang('admin/users.item.phone_unknown')</p>
                            @endif

                            <h2 class="subtitle is-5">@lang('admin/users.item.address_info')</h2>
                            @if ($user->address != null && $user->postcode != null && $user->city != null)
                                <p>{{ $user->address }}</p>
                                <p>{{ $user->postcode }}, {{ $user->city }}</p>
                            @else
                                <p>@lang('admin/users.item.address_unknown') </p>
                            @endif

                            <h2 class="subtitle is-5">@lang('admin/users.item.balance_info')</h2>

                            <p>{{ $startDate }} - {{ date('Y-m-d') }}</p>

                            <canvas id="balance_chart_canvas" wire:ignore></canvas>
                        </div>
                    </div>

                    <script>
                        (function () {
                            const chart = new Chart(document.getElementById('balance_chart_canvas').getContext('2d'), {
                                type: 'line',
                                data: {
                                    datasets: [{
                                        label: 'Balance ({{ App\Models\Setting::get('currency_symbol') }})',
                                        data: @json($user->getBalanceChart($startDate, date('Y-m-d'))),
                                        borderColor: getComputedStyle(document.querySelector('.is-link')).backgroundColor,
                                        tension: 0.1
                                    }]
                                },
                                options: {
                                    animation: false
                                }
                            });
                        })();
                    </script>
                </div>
            </div>
        </div>
    @endif

    @if ($isEditing)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isEditing', false)"></div>

            <form wire:submit.prevent="editUser" class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/users.item.edit_user')</p>
                    <button type="button" class="delete" wire:click="$set('isEditing', false)"></button>
                </div>

                <div class="modal-card-body">
                    <div class="columns">
                        <div class="column">
                            <div class="field">
                                <label class="label" for="firstname">@lang('admin/users.item.firstname')</label>
                                <div class="control">
                                    <input class="input @error('user.firstname') is-danger @enderror" type="text" id="firstname"
                                        wire:model="user.firstname" required>
                                </div>
                                @error('user.firstname') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="column">
                            <div class="field">
                                <label class="label" for="insertion">@lang('admin/users.item.insertion')</label>
                                <div class="control">
                                    <input class="input @error('user.insertion') is-danger @enderror" type="text" id="insertion"
                                        wire:model="user.insertion">
                                </div>
                                @error('user.insertion') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="column">
                            <div class="field">
                                <label class="label" for="lastname">@lang('admin/users.item.lastname')</label>
                                <div class="control">
                                    <input class="input @error('user.lastname') is-danger @enderror" type="text" id="lastname"
                                        wire:model="user.lastname" required>
                                </div>
                                @error('user.lastname') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="columns">
                        <div class="column">
                            <div class="field">
                                <label class="label" for="gender">@lang('admin/users.item.gender')</label>
                                <div class="control">
                                    <div class="select is-fullwidth @error('user.gender') is-danger @enderror">
                                        <select id="gender" wire:model="user.gender">
                                            <option value="">@lang('admin/users.item.gender_null')</option>
                                            <option value="{{ App\Models\User::GENDER_MALE }}">@lang('admin/users.item.gender_male')</option>
                                            <option value="{{ App\Models\User::GENDER_FEMALE }}">@lang('admin/users.item.gender_female')</option>
                                            <option value="{{ App\Models\User::GENDER_OTHER }}">@lang('admin/users.item.gender_other')</option>
                                        </select>
                                    </div>
                                </div>
                                @error('user.gender') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="column">
                            <div class="field">
                                <label class="label" for="birthday">@lang('admin/users.item.birthday')</label>
                                <div class="control">
                                    <input class="input @error('user.birthday') is-danger @enderror" type="date" id="birthday" wire:model="user.birthday">
                                </div>
                                @error('user.birthday') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="columns">
                        <div class="column">
                            <div class="field">
                                <label class="label" for="email">@lang('admin/users.item.email')</label>
                                <div class="control">
                                    <input class="input @error('user.email') is-danger @enderror" type="email" id="email" wire:model="user.email" required>
                                </div>
                                @error('user.email') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="column">
                            <div class="field">
                                <label class="label" for="phone">@lang('admin/users.item.phone')</label>
                                <div class="control">
                                    <input class="input @error('userPhone') is-danger @enderror" type="tel" id="phone" wire:model="user.phone">
                                </div>
                                @error('user.phone') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="columns">
                        <div class="column">
                            <div class="field">
                                <label class="label" for="address">@lang('admin/users.item.address')</label>
                                <div class="control">
                                    <input class="input @error('user.address') is-danger @enderror" type="text" id="address" wire:model="user.address">
                                </div>
                                @error('user.address') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="column">
                            <div class="field">
                                <label class="label" for="postcode">@lang('admin/users.item.postcode')</label>
                                <div class="control">
                                    <input class="input @error('user.postcode') is-danger @enderror" type="text" id="postcode" wire:model="user.postcode">
                                </div>
                                @error('user.postcode')
                                    <p class="help is-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="column">
                            <div class="field">
                                <label class="label" for="city">@lang('admin/users.item.city')</label>
                                <div class="control">
                                    <input class="input @error('user.city') is-danger @enderror" type="text" id="city" wire:model="user.city">
                                </div>
                                @error('user.city') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="columns">
                        <div class="column">
                            <div class="field">
                                <label class="label" for="password">@lang('admin/users.item.password')</label>
                                <div class="control">
                                    <input class="input @error('newPassword') is-danger @enderror" type="password" id="password" wire:model="newPassword">
                                </div>
                                @error('newPassword')
                                    <p class="help is-danger">{{ $message }}</p>
                                @else
                                    <p class="help">@lang('admin/users.item.password_hint')</p>
                                @enderror
                            </div>
                        </div>

                        <div class="column">
                            <div class="field">
                                <label class="label" for="password_confirmation">@lang('admin/users.item.password_confirmation')</label>
                                <div class="control">
                                    <input class="input @error('newPasswordConfirmation') is-danger @enderror" type="password" id="password_confirmation" wire:model="newPasswordConfirmation">
                                </div>
                                @error('newPasswordConfirmation') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="columns">
                        <div class="column">
                            <div class="field">
                                <label class="label" for="avatar">@lang('admin/users.item.avatar')</label>
                                @if ($user->avatar != null)
                                    <div class="box" style="width: 100%;">
                                        <div class="image is-square is-rounded" style="background-image: url(/storage/avatars/{{ $user->avatar }});"></div>
                                    </div>
                                @endif
                            </div>

                            <div class="field">
                                <div class="control">
                                    <input class="input @error('avatar') is-danger @enderror" type="file" accept=".jpg,.jpeg,.png" id="avatar" wire:model="avatar">
                                </div>
                                @error('avatar')
                                    <p class="help is-danger">{{ $message }}</p>
                                @else
                                    <p class="help">@lang('admin/users.item.avatar_help')</p>
                                @enderror
                            </div>

                            @if ($user->avatar != null)
                                <div class="field">
                                    <div class="control">
                                        <button type="button" class="button is-danger" wire:click="deleteAvatar" wire:loading.attr="disabled">@lang('admin/users.item.delete_avatar')</button>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="column">
                            <div class="field">
                                <label class="label" for="thanks">@lang('admin/users.item.thanks')</label>
                                @if ($user->thanks != null)
                                    <div class="box" style="width: 100%;">
                                        <div class="image is-square is-rounded" style="background-image: url(/storage/thanks/{{ $user->thanks }});"></div>
                                    </div>
                                @endif
                            </div>

                            <div class="field">
                                <div class="control">
                                    <input class="input @error('thanks') is-danger @enderror" type="file" accept=".gif" id="thanks" wire:model="thanks">
                                </div>
                                @error('thanks')
                                    <p class="help is-danger">{{ $message }}</p>
                                @else
                                    <p class="help">@lang('admin/users.item.thanks_help')</p>
                                @enderror
                            </div>

                            @if ($user->thanks != null)
                                <div class="control">
                                    <button type="button" class="button is-danger" wire:click="deleteThanks" wire:loading.attr="disabled">@lang('admin/users.item.delete_thanks')</button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="columns">
                        <div class="column">
                            <div class="field">
                                <label class="label" for="role">@lang('admin/users.item.role')</label>
                                <div class="control">
                                    <div class="select is-fullwidth @error('user.role') is-danger @enderror">
                                        <select id="role" wire:model="user.role" @if (Auth::user()->role == App\Models\User::ROLE_MANAGER && $user->role == App\Models\User::ROLE_ADMIN) disabled @endif>
                                            <option value="{{ App\Models\User::ROLE_NORMAL }}">@lang('admin/users.item.role_normal')</option>
                                            <option value="{{ App\Models\User::ROLE_MANAGER }}">@lang('admin/users.item.role_manager')</option>
                                            @if ((Auth::user()->role == App\Models\User::ROLE_MANAGER && $user->role == App\Models\User::ROLE_ADMIN) || Auth::user()->role == App\Models\User::ROLE_ADMIN)
                                                <option value="{{ App\Models\User::ROLE_ADMIN }}">@lang('admin/users.item.role_admin')</option>
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                @error('user.role')
                                    <p class="help is-danger">{{ $message }}</p>
                                @else
                                    @if (Auth::user()->role == App\Models\User::ROLE_MANAGER && $user->role == App\Models\User::ROLE_ADMIN)
                                        <p class="help">@lang('admin/users.item.role_manager_help')</p>
                                    @endif
                                @enderror
                            </div>
                        </div>

                        <div class="column">
                            <div class="field">
                                <label class="label" for="language">@lang('admin/users.item.language')</label>
                                <div class="control">
                                    <div class="select is-fullwidth @error('user.language') is-danger @enderror">
                                        <select id="language" wire:model="user.language">
                                            <option value="{{ App\Models\User::LANGUAGE_ENGLISH }}">English</option>
                                            <option value="{{ App\Models\User::LANGUAGE_DUTCH }}">Nederlands</option>
                                        </select>
                                    </div>
                                </div>
                                @error('user.language') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="column">
                            <div class="field">
                                <label class="label" for="theme">@lang('admin/users.crud.theme')</label>
                                <div class="control">
                                    <div class="select is-fullwidth @error('user.theme') is-danger @enderror">
                                        <select id="theme" wire:model="user.theme">
                                            <option value="{{ App\Models\User::THEME_LIGHT }}">@lang('admin/users.crud.theme_light')</option>
                                            <option value="{{ App\Models\User::THEME_DARK }}">@lang('admin/users.crud.theme_dark')</option>
                                            <option value="{{ App\Models\User::THEME_SYSTEM }}">@lang('admin/users.crud.theme_system')</option>
                                        </select>
                                    </div>
                                </div>
                                @error('user.theme') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="field">
                        <label class="label" for="active">@lang('admin/users.item.active')</label>
                        <label class="checkbox" for="active">
                            <input type="checkbox" id="active" wire:model="user.active">
                            @lang('admin/users.item.active_user')
                        </label>
                    </div>
                </div>

                <div class="modal-card-foot">
                    <button type="submit" class="button is-link">@lang('admin/users.item.edit_user')</button>
                    <button type="button" class="button" wire:click="$set('isEditing', false)">@lang('admin/users.item.cancel')</button>
                </div>
            </form>
        </div>
    @endif

    @if ($isDeleting)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isDeleting', false)"></div>

            <div class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/users.item.delete_user')</p>
                    <button type="button" class="delete" wire:click="$set('isDeleting', false)"></button>
                </div>

                <div class="modal-card-body">
                    <p>@lang('admin/users.item.delete_description')</p>
                </div>

                <div class="modal-card-foot">
                    <button class="button is-danger" wire:click="deleteUser()" wire:loading.attr="disabled">@lang('admin/users.item.delete_user')</button>
                    <button class="button" wire:click="$set('isDeleting', false)" wire:loading.attr="disabled">@lang('admin/users.item.cancel')</button>
                </div>
            </div>
        </div>
    @endif
</div>
