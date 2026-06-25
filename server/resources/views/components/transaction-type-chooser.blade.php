<div class="control" style="width: 100%;">
    <div class="select is-fullwidth">
        <select id="type" wire:model="type">
            <option value="">@lang('components.transaction_type_chooser_all')</option>
            <option value="transaction">@lang('components.transaction_type_chooser_transaction')</option>
            <option value="deposit">@lang('components.transaction_type_chooser_deposit')</option>
            <option value="payment">@lang('components.transaction_type_chooser_payment')</option>
        </select>
    </div>
</div>
