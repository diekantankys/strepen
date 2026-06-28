<div class="container">
    <h2 class="title">@lang('admin/users.crud.header')</h2>

    <x-search-header :itemName="__('admin/users.crud.users')">
        <div class="buttons">
            <button class="button is-link" wire:click="$set('isCreating', true)" wire:loading.attr="disabled">@lang('admin/users.crud.create')</button>
            <button class="button is-link" wire:click="recalculateBalances()" wire:loading.attr="disabled">@lang('admin/users.crud.recalculate')</button>
            <button class="button is-link" wire:click="$set('isChecking', true)" wire:loading.attr="disabled">@lang('admin/users.crud.check')</button>
            <button class="button is-link" wire:click="$set('isExporting', true)" wire:loading.attr="disabled">@lang('admin/users.crud.export')</button>
        </div>

        <x-slot name="sorters">
            <option value="">@lang('admin/users.crud.lastname_asc')</option>
            <option value="lastname_desc">@lang('admin/users.crud.lastname_desc')</option>
            <option value="firstname">@lang('admin/users.crud.firstname_asc')</option>
            <option value="firstname_desc">@lang('admin/users.crud.firstname_desc')</option>
            <option value="created_at_desc">@lang('admin/users.crud.created_at_desc')</option>
            <option value="created_at">@lang('admin/users.crud.created_at_asc')</option>
            <option value="balance_desc">@lang('admin/users.crud.balance_desc')</option>
            <option value="balance">@lang('admin/users.crud.balance_asc')</option>
        </x-slot>

        <x-slot name="filters">
            <div class="control" style="width: 100%;">
                <div class="select is-fullwidth">
                    <select id="type" wire:model="role">
                        <option value="">@lang('admin/users.crud.role_chooser_all')</option>
                        <option value="normal">@lang('admin/users.crud.role_chooser_normal')</option>
                        <option value="manager">@lang('admin/users.crud.role_chooser_manager')</option>
                        <option value="admin">@lang('admin/users.crud.role_chooser_admin')</option>
                    </select>
                </div>
            </div>
        </x-slot>
    </x-search-header>

    @if ($users->count() > 0)
        {{ $users->links() }}

        <div class="columns is-multiline">
            @foreach ($users as $user)
                <livewire:admin.users.item :user="$user" wire:key="user-{{ $user->id }}" />
            @endforeach
        </div>

        {{ $users->links() }}
    @else
        <p><i>@lang('admin/users.crud.empty')</i></p>
    @endif

    @if ($isCreating)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isCreating', false)"></div>

            <form wire:submit.prevent="createUser" class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/users.crud.create_user')</p>
                    <button type="button" class="delete" wire:click="$set('isCreating', false)"></button>
                </div>

                <div class="modal-card-body">
                    <div class="columns">
                        <div class="column">
                            <div class="field">
                                <label class="label" for="firstname">@lang('admin/users.crud.firstname')</label>
                                <div class="control">
                                    <input class="input @error('user.firstname') is-danger @enderror" type="text" id="firstname"
                                        wire:model="user.firstname" required>
                                </div>
                                @error('user.firstname') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="column">
                            <div class="field">
                                <label class="label" for="insertion">@lang('admin/users.crud.insertion')</label>
                                <div class="control">
                                    <input class="input @error('user.insertion') is-danger @enderror" type="text" id="insertion"
                                        wire:model="user.insertion">
                                </div>
                                @error('user.insertion') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="column">
                            <div class="field">
                                <label class="label" for="lastname">@lang('admin/users.crud.lastname')</label>
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
                                <label class="label" for="gender">@lang('admin/users.crud.gender')</label>
                                <div class="control">
                                    <div class="select is-fullwidth @error('user.gender') is-danger @enderror">
                                        <select id="gender" wire:model="user.gender">
                                            <option value="">@lang('admin/users.crud.gender_null')</option>
                                            <option value="{{ App\Models\User::GENDER_MALE }}">@lang('admin/users.crud.gender_male')</option>
                                            <option value="{{ App\Models\User::GENDER_FEMALE }}">@lang('admin/users.crud.gender_female')</option>
                                            <option value="{{ App\Models\User::GENDER_OTHER }}">@lang('admin/users.crud.gender_other')</option>
                                        </select>
                                    </div>
                                </div>
                                @error('user.gender') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="column">
                            <div class="field">
                                <label class="label" for="birthday">@lang('admin/users.crud.birthday')</label>
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
                                <label class="label" for="email">@lang('admin/users.crud.email')</label>
                                <div class="control">
                                    <input class="input @error('user.email') is-danger @enderror" type="email" id="email" wire:model="user.email" required>
                                </div>
                                @error('user.email') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="column">
                            <div class="field">
                                <label class="label" for="phone">@lang('admin/users.crud.phone')</label>
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
                                <label class="label" for="address">@lang('admin/users.crud.address')</label>
                                <div class="control">
                                    <input class="input @error('user.address') is-danger @enderror" type="text" id="address" wire:model="user.address">
                                </div>
                                @error('user.address') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="column">
                            <div class="field">
                                <label class="label" for="postcode">@lang('admin/users.crud.postcode')</label>
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
                                <label class="label" for="city">@lang('admin/users.crud.city')</label>
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
                                <label class="label" for="password">@lang('admin/users.crud.password')</label>
                                <div class="control">
                                    <input class="input @error('user._password') is-danger @enderror" type="password" id="password"
                                        wire:model="user._password" required>
                                </div>
                                @error('user._password')
                                    <p class="help is-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="column">
                            <div class="field">
                                <label class="label" for="password_confirmation">@lang('admin/users.crud.password_confirmation')</label>
                                <div class="control">
                                    <input class="input @error('user.password_confirmation') is-danger @enderror" type="password" id="password_confirmation"
                                        wire:model="user.password_confirmation" required>
                                </div>
                                @error('user.password_confirmation') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="columns">
                        <div class="column">
                            <div class="field">
                                <label class="label" for="avatar">@lang('admin/users.crud.avatar')</label>
                                <div class="control">
                                    <input class="input @error('avatar') is-danger @enderror" type="file" accept=".jpg,.jpeg,.png" id="avatar" wire:model="avatar">
                                </div>
                                @error('avatar')
                                    <p class="help is-danger">{{ $message }}</p>
                                @else
                                    <p class="help">@lang('admin/users.crud.avatar_help')</p>
                                @enderror
                            </div>
                        </div>
                        <div class="column">
                            <div class="field">
                                <label class="label" for="thanks">@lang('admin/users.crud.thanks')</label>
                                <div class="control">
                                    <input class="input @error('thanks') is-danger @enderror" type="file" accept=".gif" id="thanks" wire:model="thanks">
                                </div>
                                @error('thanks')
                                    <p class="help is-danger">{{ $message }}</p>
                                @else
                                    <p class="help">@lang('admin/users.crud.thanks_help')</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="columns">
                        <div class="column">
                            <div class="field">
                                <label class="label" for="role">@lang('admin/users.crud.role')</label>
                                <div class="control">
                                    <div class="select is-fullwidth @error('user.role') is-danger @enderror">
                                        <select id="role" wire:model="user.role">
                                            <option value="{{ App\Models\User::ROLE_NORMAL }}">@lang('admin/users.crud.role_normal')</option>
                                            <option value="{{ App\Models\User::ROLE_MANAGER }}">@lang('admin/users.crud.role_manager')</option>
                                            @if (Auth::user()->role == App\Models\User::ROLE_ADMIN)
                                                <option value="{{ App\Models\User::ROLE_ADMIN }}">@lang('admin/users.crud.role_admin')</option>
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                @error('user.role') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="column">
                            <div class="field">
                                <label class="label" for="language">@lang('admin/users.crud.language')</label>
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
                </div>

                <div class="modal-card-foot">
                    <button type="submit" class="button is-link">@lang('admin/users.crud.create_user')</button>
                    <button type="button" class="button" wire:click="$set('isCreating', false)" wire:loading.attr="disabled">@lang('admin/users.crud.cancel')</button>
                </div>
            </form>
        </div>
    @endif

    @if ($isChecking)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isChecking', false)"></div>

            <div class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/users.crud.check_balances')</p>
                    <button type="button" class="delete" wire:click="$set('isChecking', false)"></button>
                </div>

                <div class="modal-card-body">
                    <p>@lang('admin/users.crud.check_description')</p>
                </div>

                <div class="modal-card-foot">
                    <button class="button is-link" wire:click="checkBalances()" wire:loading.attr="disabled">@lang('admin/users.crud.check_balances')</button>
                    <button class="button" wire:click="$set('isChecking', false)" wire:loading.attr="disabled">@lang('admin/users.crud.cancel')</button>
                </div>
            </div>
        </div>
    @endif

    @if ($isExporting)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isExporting', false)"></div>

            <div class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/users.crud.export_balances')</p>
                    <button type="button" class="delete" wire:click="$set('isExporting', false)"></button>
                </div>

                <div class="modal-card-body">
                    <div class="tabs is-fullwidth">
                        <ul>
                            <li @class(['is-active' => $exportTab == 'everyone'])>
                                <a wire:click.prevent="$set('exportTab', 'everyone')">@lang('admin/users.crud.export_everyone')</a>
                            </li>
                            <li @class(['is-active' => $exportTab == 'debtors'])>
                                <a wire:click.prevent="$set('exportTab', 'debtors')">@lang('admin/users.crud.export_debtors')</a>
                            </li>
                        </ul>
                    </div>

                    @if ($exportTab == 'everyone')
                        @php
                            $users = App\Models\User::where('active', true)->orderBy('balance')->get();
                            $usersTotal = $users->map(fn ($user) => $user->balance)->sum();
                            $notActiveUsersTotal = DB::table('users')->where('active', false)->whereNull('deleted_at')->sum('balance');
                            $deletedUsersTotal = DB::table('users')->whereNotNull('deleted_at')->sum('balance');
                        @endphp
                        @foreach ($users as $user)
                            <p>{{ $user->name }}: <x-money-format :money="$user->balance" /></p>
                        @endforeach
                        <p><b>@lang('admin/users.crud.everyone_total')</b>: <x-money-format :money="$usersTotal" /></p>
                        <hr class="my-2" />
                        <p><b>@lang('admin/users.crud.not_active_total')</b>: <x-money-format :money="$notActiveUsersTotal" /></p>
                        <p><b>@lang('admin/users.crud.deleted_total')</b>: <x-money-format :money="$deletedUsersTotal" /></p>
                        <hr class="my-2" />
                        <p><b>@lang('admin/users.crud.total')</b>: <x-money-format :money="$usersTotal + $notActiveUsersTotal + $deletedUsersTotal" /></p>
                    @endif

                    @if ($exportTab == 'debtors')
                        @php
                            $users = App\Models\User::where('active', true)->where('balance', '<', App\Models\Setting::get('min_user_balance'))->orderBy('balance')->get();
                            $usersTotal = $users->map(fn ($user) => $user->balance)->sum();
                            $notDebtorUsersTotal = DB::table('users')->where('active', true)->where('balance', '>=', App\Models\Setting::get('min_user_balance'))->whereNull('deleted_at')->sum('balance');
                            $notActiveUsersTotal = DB::table('users')->where('active', false)->whereNull('deleted_at')->sum('balance');
                            $deletedUsersTotal = DB::table('users')->whereNotNull('deleted_at')->sum('balance');
                        @endphp
                        @foreach ($users as $user)
                            <p>{{ $user->name }}: <x-money-format :money="$user->balance" /></p>
                        @endforeach
                        <p><b>@lang('admin/users.crud.debtor_total')</b>: <x-money-format :money="$usersTotal" /></p>
                        <hr class="my-2" />
                        <p><b>@lang('admin/users.crud.not_debtor_total')</b>: <x-money-format :money="$notDebtorUsersTotal" /></p>
                        <p><b>@lang('admin/users.crud.not_active_total')</b>: <x-money-format :money="$notActiveUsersTotal" /></p>
                        <p><b>@lang('admin/users.crud.deleted_total')</b>: <x-money-format :money="$deletedUsersTotal" /></p>
                        <hr class="my-2" />
                        <p><b>@lang('admin/users.crud.total')</b>: <x-money-format :money="$usersTotal + $notDebtorUsersTotal + $notActiveUsersTotal + $deletedUsersTotal" /></p>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
