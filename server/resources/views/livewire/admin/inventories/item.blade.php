<div class="column is-one-third">
    <div class="card">
        <div class="card-content content">
            <h4>{{ $inventory->name }}</h4>
            <p><i>@lang('admin/inventories.item.created_by', ['user.name' => $inventory->user != null ? $inventory->user->name : '?', 'inventory.created_at' => $inventory->created_at->format('Y-m-d H:i')])</i></p>
            <x-products-amounts :products="$inventory->products()->orderByRaw('LOWER(name)')->get()" :totalPrice="$inventory->price" :createdAt="$inventory->created_at" />
        </div>

        <div class="card-footer">
            <a class="card-footer-item" wire:click.prevent="$set('isEditing', true)">@lang('admin/inventories.item.edit')</a>
            <a class="card-footer-item has-text-danger" wire:click.prevent="$set('isDeleting', true)">@lang('admin/inventories.item.delete')</a>
        </div>
    </div>

    @if ($isEditing)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isEditing', false)"></div>

            <form wire:submit.prevent="editInventory" class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/inventories.item.edit_inventory')</p>
                    <button type="button" class="delete" wire:click="$set('isEditing', false)"></button>
                </div>

                <div class="modal-card-body">
                    <livewire:components.user-chooser name="item_user" :userId="$inventory->user_id" includeInactive="true" :invalid="$this->isInputInvalid('item_user')" wire:key="admin-inventory-{{ $inventory->id }}-user-chooser" />

                    <div class="field">
                        <label class="label" for="name">@lang('admin/inventories.item.name')</label>
                        <div class="control">
                            <input class="input @error('inventory.name') is-danger @enderror" type="text" id="name"
                                wire:model="inventory.name" required>
                        </div>
                        @error('inventory.name') <p class="help is-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="columns">
                        <div class="column is-half">
                            <div class="field">
                                <label class="label" for="created_at_date">@lang('admin/inventories.item.created_at_date')</label>
                                <div class="control">
                                    <input class="input @error('createdAtDate') is-danger @enderror" type="date" id="created_at_date"
                                        wire:model="createdAtDate" required>
                                </div>
                                @error('createdAtDate') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="column is-half">
                            <div class="field">
                                <label class="label" for="created_at_time">@lang('admin/inventories.item.created_at_time')</label>
                                <div class="control">
                                    <input class="input @error('createdAtTime') is-danger @enderror" type="time" step="1" id="created_at_time"
                                        wire:model="createdAtTime" required>
                                </div>
                                @error('createdAtTime') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <livewire:components.products-chooser name="item_products" :initialProducts="$inventory->products" noMax="true" includeInactive="true" :invalid="$this->isInputInvalid('item_products')" wire:key="admin-inventory-{{ $inventory->id }}-products-chooser" />
                </div>

                <div class="modal-card-foot">
                    <button type="submit" class="button is-link" wire:loading.attr="disabled">@lang('admin/inventories.item.edit_inventory')</button>
                    <button type="button" class="button" wire:click="$set('isEditing', false)" wire:loading.attr="disabled">@lang('admin/inventories.item.cancel')</button>
                </div>
            </form>
        </div>
    @endif

    @if ($isDeleting)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isDeleting', false)"></div>

            <div class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/inventories.item.delete_inventory')</p>
                    <button type="button" class="delete" wire:click="$set('isDeleting', false)"></button>
                </div>

                <div class="modal-card-body">
                    <p>@lang('admin/inventories.item.delete_description')</p>
                </div>

                <div class="modal-card-foot">
                    <button class="button is-danger" wire:click="deleteInventory()" wire:loading.attr="disabled">@lang('admin/inventories.item.delete_inventory')</button>
                    <button class="button" wire:click="$set('isDeleting', false)" wire:loading.attr="disabled">@lang('admin/inventories.item.cancel')</button>
                </div>
            </div>
        </div>
    @endif
</div>
