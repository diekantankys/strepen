<div class="modal is-active">
    <div class="modal-background" wire:click="closeCreated"></div>

    <div class="modal-card">
        <div class="modal-card-head">
            <p class="modal-card-title">@lang('components.transaction_created_modal_header')</p>
            <button type="button" class="delete" wire:click="closeCreated"></button>
        </div>

        <div class="modal-card-body">
            <div class="box mb-5" style="width: 50%; margin: 0 auto;">
                <div class="image is-square is-rounded" style="background-image: @if ($transaction->user->thanks != null) url(/storage/thanks/{{ $transaction->user->thanks }}) @else url(/storage/thanks/{{ App\Models\Setting::get('default_user_thanks') }}) @endif;"></div>
            </div>

            <h2 class="title mb-2 has-text-centered">@lang('components.transaction_created_modal_thanks', ['user.firstname' => $transaction->user->firstname])</h2>
            <p class="mb-5 has-text-centered">@lang('components.transaction_created_modal_new_balance'): <x-money-format :money="$transaction->user->balance"/></p>

            <div class="mb-5" style="max-width: 24rem; margin-left: auto; margin-right: auto;">
                <x-products-amounts :products="$transaction->products()->orderByRaw('LOWER(name)')->get()" :totalPrice="$transaction->price" :createdAt="$transaction->created_at" />
            </div>
        </div>

        <div class="modal-card-foot">
            <button type="button" class="button is-link is-fullwidth p-4" wire:click="closeCreated" wire:loading.attr="disabled">@lang('components.transaction_created_modal_close')</button>
        </div>
    </div>

    <script>
        function keydownListener(event) {
            if (event.key == 'Enter') {
                event.preventDefault();
                @this.closeCreated();
                window.removeEventListener('keydown', keydownListener);
            }
        }
        window.addEventListener('keydown', keydownListener);
        document.addEventListener('livewire:navigating', () => {
            window.removeEventListener('keydown', keydownListener);
        }, { once: true });
        window.scrollTo({top: 0, behavior: 'smooth'});
    </script>
</div>
