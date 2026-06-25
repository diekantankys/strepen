<div class="container">
    <x-search-header :itemName="__('transactions.history.transactions')">
        <h1 class="title is-4">@lang('transactions.history.header')</h1>

        <x-slot name="sorters">
            <option value="">@lang('transactions.history.created_at_desc')</option>
            <option value="created_at">@lang('transactions.history.created_at_asc')</option>
            <option value="name">@lang('transactions.history.name_asc')</option>
            <option value="name_desc">@lang('transactions.history.name_desc')</option>
            <option value="price_desc">@lang('transactions.history.price_desc')</option>
            <option value="price">@lang('transactions.history.price_asc')</option>
        </x-slot>

        <x-slot name="filters">
            <x-transaction-type-chooser />

            <livewire:components.product-chooser name="product_filter" :productId="$product_id" inline="true" relationship="true" wire:key="transactions-history-product-filter-chooser" />
        </x-slot>
    </x-search-header>

    @if ($transactions->count() > 0)
        {{ $transactions->links() }}

        <div class="columns is-multiline">
            @foreach ($transactions as $transaction)
                <div class="column is-half-tablet is-one-third-desktop" wire:key="{{ $transaction->id }}">
                    <div class="card">
                        <div class="card-content content">
                            <h4>{{ $transaction->name }}</h4>

                            @if ($transaction->type == App\Models\Transaction::TYPE_TRANSACTION)
                                <p><i>@lang('transactions.history.transaction_on', ['transaction.created_at' => $transaction->created_at->format('Y-m-d H:i')])</i></p>
                                <x-products-amounts :products="$transaction->products()->orderByRaw('LOWER(name)')->get()" :totalPrice="$transaction->price" :createdAt="$transaction->created_at" />
                            @endif

                            @if ($transaction->type == App\Models\Transaction::TYPE_DEPOSIT)
                                <p><i>@lang('transactions.history.deposit_on', ['transaction.created_at' => $transaction->created_at->format('Y-m-d H:i')])</i></p>
                                <p>@lang('transactions.history.amount'): <x-money-format :money="$transaction->price" /></p>
                            @endif

                            @if ($transaction->type == App\Models\Transaction::TYPE_PAYMENT)
                                <p><i>@lang('transactions.history.payment_on', ['transaction.created_at' => $transaction->created_at->format('Y-m-d H:i')])</i></p>
                                <p>@lang('transactions.history.amount'): <x-money-format :money="$transaction->price" /></p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{ $transactions->links() }}
    @else
        <p><i>@lang('transactions.history.empty')</i></p>
    @endif
</div>
