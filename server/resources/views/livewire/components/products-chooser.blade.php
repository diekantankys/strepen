<div @class(['field', 'mb-5' => $bigMode])>
    @if ($bigMode)
        <label class="label" for="productName">@lang('components.products_chooser.products')</label>

        @if (!$valid || $invalid)
            <div class="notification is-danger">
                <button type="button" class="delete" wire:click="$set('valid', true)"></button>
                <p>@lang('components.products_chooser.empty_error')</p>
            </div>
        @endif

        @if ($minor)
            <div class="field">
                <p class="help">@lang('components.products_chooser.minor')</p>
            </div>
        @endif

        <div class="columns is-multiline">
            @foreach ($filteredProducts as $product)
                @php
                    $selectedProduct = $selectedProducts->firstWhere('product_id', $product->id);
                @endphp

                <div class="column is-half-tablet is-one-third-desktop is-one-quarter-widescreen" wire:key="columns-{{ $product->id }}">
                    <div class="card">
                        <div class="card-image">
                            <div class="image is-square" style="background-image: url(/storage/products/{{ $product->image ?? App\Models\Setting::get('default_product_image') }});"></div>

                            @if ($product->alcoholic)
                                <div class="card-image-tags">
                                    <span class="tag is-danger">{{ Str::upper(__('admin/products.item.alcoholic')) }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="card-content content">
                            <h4 class="mb-3" style="font-weight: 600;">
                                {{ $product->name }}
                                <span style="float: right;"><x-money-format :money="$product->price" :isBold="false" /></span>
                            </h4>

                            <p class="mb-4 ellipsis"><i>{{ $product->description ?? __('admin/products.item.no_description') }}</i></p>

                            <div class="columns is-mobile">
                                <div class="column">
                                    <button type="button" class="button is-link is-fullwidth" wire:click="decrementProductAmount({{ $product->id }})">-</button>
                                </div>
                                <div class="column" style="display: flex; align-items: center; justify-content: center;">
                                    <h3 class="m-0">{{ $selectedProduct != null ? $selectedProduct['amount'] : 0 }}</h3>
                                </div>
                                <div class="column">
                                    <button type="button" class="button is-link is-fullwidth" wire:click="incrementProductAmount({{ $product->id }})">+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="field">
            <label class="label" for="productName">@lang('components.products_chooser.products')</label>

            @forelse ($selectedProducts as $index => $selectedProduct)
                @php
                    $product = $products->find($selectedProduct['product_id']);
                @endphp

                <div class="media" style="align-items: center;" wire:key="fields-{{ $product->id }}">
                    <div class="media-left">
                        <div class="image is-large is-rounded" style="background-image: url(/storage/products/{{ $product->image ?? App\Models\Setting::get('default_product_image') }});"></div>
                    </div>
                    <div class="media-content">
                        <label class="label" for="product-amount-{{ $index }}" style="font-weight: 600;">
                            {{ $product->name }}
                            <button type="button" class="delete is-pulled-right" style="transform: translateY(3px);" wire:click="deleteProduct({{ $product->id }})"></button>
                        </label>

                        <div class="columns">
                            <div class="column">
                                <div class="control has-icons-left">
                                    <input @class(['input', 'is-danger' => !$valid || $invalid]) type="number" step="0.01" placeholder="@lang('components.products_chooser.price')"
                                        id="product-price-{{ $index }}" wire:model.live.debounce.150ms="selectedProducts.{{ $index }}.price" required>
                                    <span class="icon is-small is-left">{{ App\Models\Setting::get('currency_symbol') }}</span>
                                </div>
                            </div>
                            <div class="column" style="flex: 0 0 auto; align-self: center;">
                                &times;
                            </div>
                            <div class="column">
                                <div class="control">
                                    <input @class(['input', 'is-danger' => !$valid || $invalid]) type="number" placeholder="@lang('components.products_chooser.amount')"
                                        min="1" @if (!$noMax) max="{{ App\Models\Setting::get('max_stripe_amount') }}" @endif id="product-amount-{{ $index }}"
                                        wire:model.live.debounce.150ms="selectedProducts.{{ $index }}.amount" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <p><i>@lang('components.products_chooser.products_help')</i></p>
            @endforelse
        </div>

        <div class="field">
            <div @class(['dropdown', 'is-active' => $isOpen, 'control']) style="width: 100%;">
                <div class="dropdown-trigger control" style="width: 100%;">
                    <input id="products-chooser-input-{{ $htmlInputId }}" @class(['input', 'is-danger' => !$valid || $invalid]) type="text"
                        placeholder="@lang('components.products_chooser.search_product')"
                        autocomplete="off" wire:model.live.debounce.150ms="productName" wire:focus="$set('isOpen', true)"
                        data-chooser-input data-chooser-type="products" data-chooser-dropdown="products-chooser-dropdown-{{ $htmlInputId }}">
                </div>
                <div class="dropdown-menu" style="width: 100%;">
                    <div id="products-chooser-dropdown-{{ $htmlInputId }}" class="dropdown-content">
                        @forelse ($filteredProducts as $product)
                            <a wire:click.prevent="addProduct({{ $product->id }})" class="dropdown-item" wire:key="dropdown-{{ $product->id }}" data-chooser-option data-chooser-value="{{ $product->id }}">
                                <div class="image is-small is-rounded is-inline" style="background-image: url(/storage/products/{{ $product->image ?? 'default.png' }});"></div>
                                {!! $productName != '' ? str_replace(' ', '&nbsp;', preg_replace('#(' . preg_quote($productName) . ')#i', '<b>$1</b>', $product->name)) : $product->name !!}
                            </a>
                        @empty
                            <div class="dropdown-item"><i>@lang('components.products_chooser.search_empty')</i></div>
                        @endforelse
                    </div>
                </div>
            </div>

            @if ($minor)
                <p class="help">@lang('components.products_chooser.minor')</p>
            @endif

            @if (!$valid || $invalid)
                <p class="help is-danger">@lang('components.products_chooser.empty_error')</p>
            @endif

        </div>
    @endif
</div>
