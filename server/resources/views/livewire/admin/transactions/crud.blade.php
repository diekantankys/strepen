<div class="container">
    <h2 class="title">@lang('admin/transactions.crud.header')</h2>

    <x-search-header :itemName="__('admin/transactions.crud.transactions')">
        <div class="buttons">
            <button class="button is-link" wire:click="openCreateTransaction" wire:loading.attr="disabled">@lang('admin/transactions.crud.create_transaction')</button>
            <button class="button is-link" wire:click="openCreateDeposit" wire:loading.attr="disabled">@lang('admin/transactions.crud.create_deposit_small')</button>
            <button class="button is-link" wire:click="openCreatePayment" wire:loading.attr="disabled">@lang('admin/transactions.crud.create_payment_small')</button>
        </div>

        <x-slot name="sorters">
            <option value="">@lang('admin/transactions.crud.created_at_desc')</option>
            <option value="created_at">@lang('admin/transactions.crud.created_at_asc')</option>
            <option value="name">@lang('admin/transactions.crud.name_asc')</option>
            <option value="name_desc">@lang('admin/transactions.crud.name_desc')</option>
            <option value="price_desc">@lang('admin/transactions.crud.price_desc')</option>
            <option value="price">@lang('admin/transactions.crud.price_asc')</option>
        </x-slot>

        <x-slot name="filters">
            <x-transaction-type-chooser />

            <livewire:components.user-chooser name="user_filter" :userId="$user_id" includeInactive="true" inline="true" relationship="true" wire:key="admin-transactions-filter-user-chooser" />

            <livewire:components.product-chooser name="product_filter" :productId="$product_id" includeInactive="true" inline="true" relationship="true" wire:key="admin-transactions-filter-product-chooser" />
        </x-slot>
    </x-search-header>

    @if ($transactions->count() > 0)
        {{ $transactions->links() }}

        <div class="columns is-multiline">
            @foreach ($transactions as $transaction)
                <livewire:admin.transactions.item :transaction="$transaction" wire:key="transaction-{{ $transaction->id }}" />
            @endforeach
        </div>

        {{ $transactions->links() }}
    @else
        <p><i>@lang('admin/transactions.crud.empty')</i></p>
    @endif

    @if ($isCreatingTransaction)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isCreatingTransaction', false)"></div>

            <form wire:submit.prevent="createTransaction" class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/transactions.crud.create_transaction')</p>
                    <button type="button" class="delete" wire:click="$set('isCreatingTransaction', false)"></button>
                </div>

                <div class="modal-card-body">
                    <livewire:components.user-chooser name="user" includeInactive="true" sortBy="last_transaction" :invalid="$this->isInputInvalid('user')" wire:key="admin-transactions-create-transaction-user-chooser" />

                    <div class="field">
                        <label class="label" for="name">@lang('admin/transactions.crud.name')</label>
                        <div class="control">
                            <input class="input @error('transaction.name') is-danger @enderror" type="text" id="name"
                                wire:model="transaction.name" required>
                        </div>
                        @error('transaction.name') <p class="help is-danger">{{ $message }}</p> @enderror
                    </div>

                    <livewire:components.products-chooser name="products" noMax="true" includeInactive="true" :invalid="$this->isInputInvalid('products')" wire:key="admin-transactions-create-transaction-products-chooser" />
                </div>

                <div class="modal-card-foot">
                    <button type="submit" class="button is-link" wire:loading.attr="disabled">@lang('admin/transactions.crud.create_transaction')</button>
                    <button type="button" class="button" wire:click="$set('isCreatingTransaction', false)" wire:loading.attr="disabled">@lang('admin/transactions.crud.cancel')</button>
                </div>
            </form>
        </div>
    @endif

    @if ($isCreatingDeposit)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isCreatingDeposit', false)"></div>

            <form wire:submit.prevent="createDeposit" class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/transactions.crud.create_deposit')</p>
                    <button type="button" class="delete" wire:click="$set('isCreatingDeposit', false)"></button>
                </div>

                <div class="modal-card-body">
                    <div class="field">
                        <label class="label" for="name">@lang('admin/transactions.crud.name')</label>
                        <div class="control">
                            <input class="input @error('transaction.name') is-danger @enderror" type="text" id="name"
                                wire:model="transaction.name" required>
                        </div>
                        @error('transaction.name') <p class="help is-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="tabs is-fullwidth">
                        <ul>
                            <li @class(['is-active' => $creatingDepositTab == 'single'])>
                                <a wire:click.prevent="$set('creatingDepositTab', 'single')">@lang('admin/transactions.crud.single')</a>
                            </li>
                            <li @class(['is-active' => $creatingDepositTab == 'multiple'])>
                                <a wire:click.prevent="$set('creatingDepositTab', 'multiple')">@lang('admin/transactions.crud.multiple')</a>
                            </li>
                        </ul>
                    </div>

                    @if ($creatingDepositTab == 'single')
                        <livewire:components.user-chooser name="user" includeInactive="true" :invalid="$this->isInputInvalid('user')" wire:key="admin-transactions-create-deposit-user-chooser" />

                        <div class="field">
                            <label class="label" for="amount">@lang('admin/transactions.crud.amount')</label>
                            <div class="control has-icons-left">
                                <input class="input @error('transaction.price') is-danger @enderror" type="number" step="0.01" id="amount"
                                    wire:model="transaction.price" required>
                                <span class="icon is-small is-left">{{ App\Models\Setting::get('currency_symbol') }}</span>
                            </div>
                            @error('transaction.price') <p class="help is-danger">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if ($creatingDepositTab == 'multiple')
                        <table class="table is-fullwidth">
                            <thead>
                                <tr>
                                    <th>@lang('admin/transactions.crud.name')</th>
                                    <th>@lang('admin/transactions.crud.amount')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $index => $user)
                                    <tr>
                                        <td style="vertical-align: middle;">
                                            <div class="image is-small is-round is-inline" style="background-image: url(/storage/avatars/{{ $user->avatar ?? App\Models\Setting::get('default_user_avatar') }});"></div>
                                            <label for="user-amount-{{ $index }}">{{ $user->name }}</label>
                                        </td>
                                        <td>
                                            <div class="control has-icons-left">
                                                <input class="input @error('transaction.price') is-danger @enderror" type="number" step="0.01" id="user-amount-{{ $index }}"
                                                    wire:model="userAmounts.{{ $index }}">
                                                <span class="icon is-small is-left">{{ App\Models\Setting::get('currency_symbol') }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div class="modal-card-foot">
                    <button type="submit" class="button is-link">@lang('admin/transactions.crud.create_deposit')</button>
                    <button type="button" class="button" wire:click="$set('isCreatingDeposit', false)" wire:loading.attr="disabled">@lang('admin/transactions.crud.cancel')</button>
                </div>
            </form>
        </div>
    @endif

    @if ($isCreatingPayment)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isCreatingPayment', false)"></div>

            <form wire:submit.prevent="createPayment" class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/transactions.crud.create_payment')</p>
                    <button type="button" class="delete" wire:click="$set('isCreatingPayment', false)"></button>
                </div>

                <div class="modal-card-body">
                    <div class="field">
                        <label class="label" for="name">@lang('admin/transactions.crud.name')</label>
                        <div class="control">
                            <input class="input @error('transaction.name') is-danger @enderror" type="text" id="name"
                                wire:model="transaction.name" required>
                        </div>
                        @error('transaction.name') <p class="help is-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="tabs is-fullwidth">
                        <ul>
                            <li @class(['is-active' => $creatingPaymentTab == 'single'])>
                                <a wire:click.prevent="$set('creatingPaymentTab', 'single')">@lang('admin/transactions.crud.single')</a>
                            </li>
                            <li @class(['is-active' => $creatingPaymentTab == 'multiple'])>
                                <a wire:click.prevent="$set('creatingPaymentTab', 'multiple')">@lang('admin/transactions.crud.multiple')</a>
                            </li>
                        </ul>
                    </div>

                    @if ($creatingPaymentTab == 'single')
                        <livewire:components.user-chooser name="user" includeInactive="true" :invalid="$this->isInputInvalid('user')" wire:key="admin-transactions-create-payment-user-chooser" />

                        <div class="field">
                            <label class="label" for="amount">@lang('admin/transactions.crud.amount')</label>
                            <div class="control has-icons-left">
                                <input class="input @error('transaction.price') is-danger @enderror" type="number" step="0.01" id="amount"
                                    wire:model="transaction.price" required>
                                <span class="icon is-small is-left">{{ App\Models\Setting::get('currency_symbol') }}</span>
                            </div>
                            @error('transaction.price') <p class="help is-danger">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if ($creatingPaymentTab == 'multiple')
                        <table class="table is-fullwidth">
                            <thead>
                                <tr>
                                    <th>@lang('admin/transactions.crud.name')</th>
                                    <th>@lang('admin/transactions.crud.amount')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $index => $user)
                                    <tr>
                                        <td style="vertical-align: middle;">
                                            <div class="image is-small is-round is-inline" style="background-image: url(/storage/avatars/{{ $user->avatar ?? App\Models\Setting::get('default_user_avatar') }});"></div>
                                            <label for="user-amount-{{ $index }}">{{ $user->name }}</label>
                                        </td>
                                        <td>
                                            <div class="control has-icons-left">
                                                <input class="input @error('transaction.price') is-danger @enderror" type="number" step="0.01" id="user-amount-{{ $index }}"
                                                    wire:model="userAmounts.{{ $index }}">
                                                <span class="icon is-small is-left">{{ App\Models\Setting::get('currency_symbol') }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div class="modal-card-foot">
                    <button type="submit" class="button is-link">@lang('admin/transactions.crud.create_payment')</button>
                    <button type="button" class="button" wire:click="$set('isCreatingPayment', false)" wire:loading.attr="disabled">@lang('admin/transactions.crud.cancel')</button>
                </div>
            </form>
        </div>
    @endif
</div>
