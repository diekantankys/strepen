<div class="container">
    <h2 class="title">@lang('admin/products.crud.header')</h2>

    <x-search-header :itemName="__('admin/products.crud.products')">
        <div class="buttons">
            <button class="button is-link" wire:click="$set('isCreating', true)" wire:loading.attr="disabled">@lang('admin/products.crud.create_product')</button>
            <button class="button is-link" wire:click="recalculateAmounts()" wire:loading.attr="disabled">@lang('admin/products.crud.recalculate_amounts')</button>
        </div>

        <x-slot name="sorters">
            <option value="">@lang('admin/products.crud.name_asc')</option>
            <option value="name_desc">@lang('admin/products.crud.name_desc')</option>
            <option value="created_at_desc">@lang('admin/products.crud.created_at_desc')</option>
            <option value="created_at">@lang('admin/products.crud.created_at_asc')</option>
            <option value="price_desc">@lang('admin/products.crud.price_desc')</option>
            <option value="price">@lang('admin/products.crud.price_asc')</option>
        </x-slot>

        <x-slot name="filters">
            <div class="control" style="width: 100%;">
                <div class="select is-fullwidth">
                    <select id="type" wire:model="alcoholic">
                        <option value="">@lang('admin/products.crud.alcoholic_chooser_all')</option>
                        <option value="yes">@lang('admin/products.crud.alcoholic_chooser_yes')</option>
                        <option value="no">@lang('admin/products.crud.alcoholic_chooser_no')</option>
                    </select>
                </div>
            </div>
        </x-slot>
    </x-search-header>

    @if ($products->count() > 0)
        {{ $products->links() }}

        <div class="columns is-multiline">
            @foreach ($products as $product)
                <livewire:admin.products.item :product="$product" wire:key="product-{{ $product->id }}" />
            @endforeach
        </div>

        {{ $products->links() }}
    @else
        <p><i>@lang('admin/products.crud.empty')</i></p>
    @endif

    @if ($isCreating)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isCreating', false)"></div>

            <form wire:submit.prevent="createProduct" class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/products.crud.create_product')</p>
                    <button type="button" class="delete" wire:click="$set('isCreating', false)"></button>
                </div>

                <div class="modal-card-body">
                    <div class="field">
                        <label class="label" for="name">@lang('admin/products.crud.name')</label>
                        <div class="control">
                            <input class="input @error('product.name') is-danger @enderror" type="text" id="name"
                                wire:model="product.name" required>
                        </div>
                        @error('product.name') <p class="help is-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="price">@lang('admin/products.crud.price')</label>
                        <div class="control has-icons-left">
                            <input class="input @error('product.price') is-danger @enderror" type="number" step="0.01" id="price"
                                wire:model="product.price" required>
                            <span class="icon is-small is-left">{{ App\Models\Setting::get('currency_symbol') }}</span>
                        </div>
                        @error('product.price') <p class="help is-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="description">@lang('admin/products.crud.description')</label>
                        <div class="control">
                            <textarea class="textarea is-family-monospace has-fixed-size @error('product.description') is-danger @enderror" id="description"
                                wire:model="product.description"></textarea>
                        </div>
                        @error('product.description') <p class="help is-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="image">@lang('admin/products.crud.image')</label>
                        <div class="control">
                            <input class="input @error('image') is-danger @enderror" type="file" accept=".jpg,.jpeg,.png"
                                id="image" wire:model="image">
                        </div>
                        @error('image')
                            <p class="help is-danger">{{ $message }}</p>
                        @else
                            <p class="help">@lang('admin/products.crud.image_help')</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="alcoholic">@lang('admin/products.crud.alcoholic')</label>
                        <label class="checkbox" for="alcoholic">
                            <input type="checkbox" id="alcoholic" wire:model="product.alcoholic">
                            @lang('admin/products.crud.alcoholic_product')
                        </label>
                    </div>
                </div>

                <div class="modal-card-foot">
                    <button type="submit" class="button is-link">@lang('admin/products.crud.create_product')</button>
                    <button type="button" class="button" wire:click="$set('isCreating', false)" wire:loading.attr="disabled">@lang('admin/products.crud.cancel')</button>
                </div>
            </form>
        </div>
    @endif
</div>
