<div class="column is-one-third">
    <div class="card">
        <div class="card-content content">
            <h4>{{ $transaction->name }}</h4>

            @if ($transaction->type == App\Models\Transaction::TYPE_TRANSACTION)
                <p><i>@lang('admin/transactions.item.transaction_from', ['user.name' => $transaction->user != null ? $transaction->user->name : '?', 'transaction.created_at' => $transaction->created_at->format('Y-m-d H:i')])</i></p>
                <x-products-amounts :products="$transaction->products()->orderByRaw('LOWER(name)')->get()" :totalPrice="$transaction->price" :createdAt="$transaction->created_at" />
            @endif

            @if ($transaction->type == App\Models\Transaction::TYPE_DEPOSIT)
                <p><i>@lang('admin/transactions.item.deposit_for', ['user.name' => $transaction->user != null ? $transaction->user->name : '?', 'transaction.created_at' => $transaction->created_at->format('Y-m-d H:i')])</i></p>
                <p>@lang('admin/transactions.item.amount'): <x-money-format :money="$transaction->price" /></p>
            @endif

            @if ($transaction->type == App\Models\Transaction::TYPE_PAYMENT)
                <p><i>@lang('admin/transactions.item.payment_for', ['user.name' => $transaction->user != null ? $transaction->user->name : '?', 'transaction.created_at' => $transaction->created_at->format('Y-m-d H:i')])</i></p>
                <p>@lang('admin/transactions.item.amount'): <x-money-format :money="$transaction->price" /></p>
            @endif
        </div>

        <div class="card-footer">
            <a class="card-footer-item" wire:click.prevent="$set('isEditing', true)">@lang('admin/transactions.item.edit')</a>
            <a class="card-footer-item has-text-danger" wire:click.prevent="$set('isDeleting', true)">@lang('admin/transactions.item.delete')</a>
        </div>
    </div>

    @if ($isEditing)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isEditing', false)"></div>

            <form wire:submit.prevent="editTransaction" class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/transactions.item.edit_transaction')</p>
                    <button type="button" class="delete" wire:click="$set('isEditing', false)"></button>
                </div>

                <div class="modal-card-body">
                    <livewire:components.user-chooser name="item_user" :userId="$transaction->user_id" includeInactive="true" :invalid="$this->isInputInvalid('item_user')" wire:key="admin-transaction-{{ $transaction->id }}-user-chooser" />

                    <div class="field">
                        <label class="label" for="name">@lang('admin/transactions.item.name')</label>
                        <div class="control">
                            <input class="input @error('transaction.name') is-danger @enderror" type="text" id="name"
                                wire:model="transaction.name" required>
                        </div>
                        @error('transaction.name') <p class="help is-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="columns">
                        <div class="column">
                            <div class="field">
                                <label class="label" for="created_at_date">@lang('admin/transactions.item.created_at_date')</label>
                                <div class="control">
                                    <input class="input @error('createdAtDate') is-danger @enderror" type="date" id="created_at_date"
                                        wire:model="createdAtDate" required>
                                </div>
                                @error('createdAtDate') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="column">
                            <div class="field">
                                <label class="label" for="created_at_time">@lang('admin/transactions.item.created_at_time')</label>
                                <div class="control">
                                    <input class="input @error('createdAtTime') is-danger @enderror" type="time" step="1" id="created_at_time"
                                        wire:model="createdAtTime" required>
                                </div>
                                @error('createdAtTime') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    @if ($transaction->type == App\Models\Transaction::TYPE_TRANSACTION)
                        <livewire:components.products-chooser name="item_products" :initialProducts="$transaction->products" noMax="true" includeInactive="true" :invalid="$this->isInputInvalid('item_products')" wire:key="admin-transaction-{{ $transaction->id }}-products-chooser" />
                    @endif

                    @if ($transaction->type == App\Models\Transaction::TYPE_DEPOSIT || $transaction->type == App\Models\Transaction::TYPE_PAYMENT)
                        <div class="field">
                            <label class="label" for="amount">@lang('admin/transactions.item.amount')</label>
                            <div class="control has-icons-left">
                                <input class="input @error('transaction.price') is-danger @enderror" type="number" step="0.01" id="amount"
                                    wire:model="transaction.price" required>
                                <span class="icon is-small is-left">{{ App\Models\Setting::get('currency_symbol') }}</span>
                            </div>
                            @error('transaction.price') <p class="help is-danger">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>

                <div class="modal-card-foot">
                    <button type="submit" class="button is-link" wire:loading.attr="disabled">@lang('admin/transactions.item.edit_transaction')</button>
                    <button type="button" class="button" wire:click="$set('isEditing', false)" wire:loading.attr="disabled">@lang('admin/transactions.item.cancel')</button>
                </div>
            </form>
        </div>
    @endif

    @if ($isDeleting)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isDeleting', false)"></div>

            <div class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/transactions.item.delete_transaction')</p>
                    <button type="button" class="delete" wire:click="$set('isDeleting', false)"></button>
                </div>

                <div class="modal-card-body">
                    <p>@lang('admin/transactions.item.delete_description')</p>
                </div>

                <div class="modal-card-foot">
                    <button class="button is-danger" wire:click="deleteTransaction()" wire:loading.attr="disabled">@lang('admin/transactions.item.delete_transaction')</button>
                    <button class="button" wire:click="$set('isDeleting', false)" wire:loading.attr="disabled">@lang('admin/transactions.item.cancel')</button>
                </div>
            </div>
        </div>
    @endif
</div>
