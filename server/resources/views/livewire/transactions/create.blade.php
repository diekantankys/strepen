<div class="container">
    @if (Auth::id() == 1)
        <h1 class="title">@lang('transactions.create.header_kiosk')</h1>
    @else
        <h1 class="title">@lang('transactions.create.header_no_kiosk')</h1>
    @endif

    <form class="box" id="create_transaction_form" wire:submit.prevent="createTransaction">
        @if (Auth::id() == 1)
            <livewire:components.user-chooser name="user" sortBy="last_transaction" :invalid="$this->isInputInvalid('user')" wire:key="transactions-create-user-chooser" />
        @endif

        <div class="field">
            <label class="label" for="name">@lang('transactions.create.name')</label>
            <div class="control">
                <input class="input @error('transaction.name') is-danger @enderror" type="text" id="name"
                    wire:model="transaction.name" required>
            </div>
            @error('transaction.name') <p class="help is-danger">{{ $message }}</p> @enderror
        </div>

        <livewire:components.products-chooser name="products" :minor="Auth::user()->minor" bigMode="true" :invalid="$this->isInputInvalid('products')" wire:key="transactions-create-products-chooser" />

        <div class="field">
            <div class="control">
                <button type="submit" class="button is-link is-fullwidth p-4" wire:loading.attr="disabled">@lang('transactions.create.create_transaction')</button>
            </div>
        </div>
    </form>

    @if ($isCreated)
        <x-transaction-created-modal :transaction="$transaction" />
    @endif

    <script>
        document.addEventListener('livewire:navigated', () => {
            const form = document.getElementById('create_transaction_form');
            if (form == null || form.dataset.initialized === 'true') {
                return;
            }

            form.dataset.initialized = 'true';

            const keydownListener = event => {
                if (event.key == 'Enter' && !@this.isCreated) {
                    event.preventDefault();
                    @this.createTransaction();
                }
            };

            window.addEventListener('keydown', keydownListener);

            const cleanupScrollTop = Livewire.on('scroll-top', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            document.addEventListener('livewire:navigating', () => {
                window.removeEventListener('keydown', keydownListener);
                cleanupScrollTop();
            }, { once: true });
        }, { once: true });
    </script>
</div>
