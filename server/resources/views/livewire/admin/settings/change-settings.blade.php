<div style="display: flex; flex-direction: column; height: 100%;">
    @if ($isChanged)
        <div class="notification is-success">
            <button class="delete" wire:click="$set('isChanged', false)"></button>
            <p>@lang('admin/settings.change_settings.success_message')</p>
        </div>
    @endif

    <form class="box" style="flex: 1;" wire:submit.prevent="changeDetails">
        <h2 class="title is-4">@lang('admin/settings.change_settings.header')</h2>

        <div class="columns">
            <div class="column">
                <div class="field">
                    <label class="label" for="currencySymbol">@lang('admin/settings.change_settings.currency_symbol')</label>
                    <div class="control">
                        <input class="input @error('currencySymbol') is-danger @enderror" type="text" id="currencySymbol"
                            wire:model="currencySymbol">
                    </div>
                    @error('currencySymbol') <p class="help is-danger">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="column">
                <div class="field">
                    <label class="label" for="currencyName">@lang('admin/settings.change_settings.currency_name')</label>
                    <div class="control">
                        <input class="input @error('currencyName') is-danger @enderror" type="text" id="currencyName"
                            wire:model="currencyName">
                    </div>
                    @error('currencyName') <p class="help is-danger">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="columns">
            <div class="column">
                <div class="field">
                    <label class="label" for="minUserBalance">@lang('admin/settings.change_settings.min_user_balance')</label>

                    <div class="control has-icons-left">
                        <input class="input @error('minUserBalance') is-danger @enderror" type="number" id="minUserBalance"
                            wire:model="minUserBalance" required>
                        <span class="icon is-small is-left">{{ App\Models\Setting::get('currency_symbol') }}</span>
                    </div>
                    @error('minUserBalance') <p class="help is-danger">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="column">
                <div class="field">
                    <label class="label" for="maxStripeAmount">@lang('admin/settings.change_settings.max_stripe_amount')</label>
                    <div class="control">
                        <input class="input @error('maxStripeAmount') is-danger @enderror" type="number" id="maxStripeAmount"
                            wire:model="maxStripeAmount" required>
                    </div>
                    @error('maxStripeAmount') <p class="help is-danger">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="columns">
            <div class="column">
                <div class="field">
                    <label class="label" for="minorAge">@lang('admin/settings.change_settings.minor_age')</label>
                    <div class="control">
                        <input class="input @error('minorAge') is-danger @enderror" type="number" id="minorAge"
                            wire:model="minorAge" required>
                    </div>
                    @error('minorAge') <p class="help is-danger">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="column">
                <div class="field">
                    <label class="label" for="paginationRows">@lang('admin/settings.change_settings.pagination_rows')</label>
                    <div class="control">
                        <input class="input @error('paginationRows') is-danger @enderror" type="number" id="paginationRows"
                            wire:model="paginationRows" required>
                    </div>
                    @error('paginationRows') <p class="help is-danger">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="field mb-5">
            <label class="label" for="kioskIpWhitelist">@lang('admin/settings.change_settings.kiosk_ip_whitelist')</label>
            <div class="control">
                <input class="input @error('kioskIpWhitelist') is-danger @enderror" type="text" id="kioskIpWhitelist"
                    wire:model="kioskIpWhitelist">
            </div>
            @error('kioskIpWhitelist') <p class="help is-danger">{{ $message }}</p> @enderror
        </div>

        <div class="columns">
            <div class="column">
                <div class="field">
                    <label class="label" for="leaderboardsEnabled">@lang('admin/settings.change_settings.leaderboards_enabled')</label>
                    <label class="checkbox" for="leaderboardsEnabled">
                        <input type="checkbox" id="leaderboardsEnabled" wire:model="leaderboardsEnabled">
                        @lang('admin/settings.change_settings.leaderboards_enabled_info')
                    </label>
                </div>
            </div>
        </div>

        <div class="columns">
            <div class="column">
                <div class="field">
                    <label class="label" for="bankAccountIban">@lang('admin/settings.change_settings.bank_account_iban')</label>
                    <div class="control">
                        <input class="input @error('bankAccountIban') is-danger @enderror" type="text" id="bankAccountIban"
                            wire:model="bankAccountIban">
                    </div>
                    @error('bankAccountIban') <p class="help is-danger">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="column">
                <div class="field">
                    <label class="label" for="bankAccountHolder">@lang('admin/settings.change_settings.bank_account_holder')</label>
                    <div class="control">
                        <input class="input @error('bankAccountHolder') is-danger @enderror" type="text" id="bankAccountHolder"
                            wire:model="bankAccountHolder">
                    </div>
                    @error('bankAccountHolder') <p class="help is-danger">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="columns">
            <div class="column">
                <div class="field">
                    <label class="label">@lang('admin/settings.change_settings.product_beer_ids')</label>
                    <p class="mb-2">
                        @forelse ($productBeerIds as $id)
                            <span class="tag">
                                {{ App\Models\Product::withTrashed()->find($id)?->name }}
                                <button class="delete is-small" style="margin-left: 0.5em;" wire:click.prevent="removeProductId('beer', '{{ $id }}')"></button>
                            </span>
                        @empty
                           <i>@lang('admin/settings.change_settings.no_products_selected')</i>
                        @endforelse
                    </p>
                    <livewire:components.product-chooser name="product_beer" inline="true" includeInactive="true" includeDeleted="true" wire:key="admin-settings-product-beer-chooser" />
                    @error('productBeerIds') <p class="help is-danger">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="column">
                <div class="field">
                    <label class="label">@lang('admin/settings.change_settings.product_soda_ids')</label>
                    <p class="mb-2">
                        @forelse ($productSodaIds as $id)
                            <span class="tag">
                                {{ App\Models\Product::withTrashed()->find($id)?->name }}
                                <button class="delete is-small" style="margin-left: 0.5em;" wire:click.prevent="removeProductId('soda', '{{ $id }}')"></button>
                            </span>
                        @empty
                           <i>@lang('admin/settings.change_settings.no_products_selected')</i>
                        @endforelse
                    </p>
                    <livewire:components.product-chooser name="product_soda" inline="true" includeInactive="true" includeDeleted="true" wire:key="admin-settings-product-soda-chooser" />
                    @error('productSodaIds') <p class="help is-danger">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="column">
                <div class="field">
                    <label class="label">@lang('admin/settings.change_settings.product_snack_ids')</label>
                    <p class="mb-2">
                        @forelse ($productSnackIds as $id)
                            <span class="tag">
                                {{ App\Models\Product::withTrashed()->find($id)?->name }}
                                <button class="delete is-small" style="margin-left: 0.5em;" wire:click.prevent="removeProductId('snack', '{{ $id }}')"></button>
                            </span>
                        @empty
                           <i>@lang('admin/settings.change_settings.no_products_selected')</i>
                        @endforelse
                    </p>
                    <livewire:components.product-chooser name="product_snack" inline="true" includeInactive="true" includeDeleted="true" wire:key="admin-settings-product-snack-chooser" />
                    @error('productSnackIds') <p class="help is-danger">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="field">
            <div class="control">
                <button class="button is-link" type="submit">@lang('admin/settings.change_settings.button')</button>
            </div>
        </div>
    </form>
</div>
