<div class="container">
    <h2 class="title">@lang('admin/api_keys.crud.header')</h2>

    <x-search-header :itemName="__('admin/api_keys.crud.api_keys')">
        <div class="buttons">
            <button class="button is-link" wire:click="$set('isCreating', true)" wire:loading.attr="disabled">@lang('admin/api_keys.crud.create_api_key')</button>
        </div>

        <x-slot name="sorters">
            <option value="">@lang('admin/api_keys.crud.name_asc')</option>
            <option value="name_desc">@lang('admin/api_keys.crud.name_desc')</option>
            <option value="created_at_desc">@lang('admin/api_keys.crud.created_at_desc')</option>
            <option value="created_at">@lang('admin/api_keys.crud.created_at_asc')</option>
        </x-slot>
    </x-search-header>

    @if ($apiKeys->count() > 0)
        {{ $apiKeys->links() }}

        <div class="columns is-multiline">
            @foreach ($apiKeys as $apiKey)
                <livewire:admin.api-keys.item :apiKey="$apiKey" wire:key="api-key-{{ $apiKey->id }}" />
            @endforeach
        </div>

        {{ $apiKeys->links() }}
    @else
        <p><i>@lang('admin/api_keys.crud.empty')</i></p>
    @endif

    @if ($isCreating)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isCreating', false)"></div>

            <form wire:submit.prevent="createApiKey" class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/api_keys.crud.create_api_key')</p>
                    <button type="button" class="delete" wire:click="$set('isCreating', false)"></button>
                </div>

                <div class="modal-card-body">
                    <div class="field">
                        <label class="label" for="name">@lang('admin/api_keys.crud.name')</label>
                        <div class="control">
                            <input class="input @error('apiKey.name') is-danger @enderror" type="text" id="name"
                                wire:model="apiKey.name" required>
                        </div>
                        @error('apiKey.name') <p class="help is-danger">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="modal-card-foot">
                    <button type="submit" class="button is-link">@lang('admin/api_keys.crud.create_api_key')</button>
                    <button type="button" class="button" wire:click="$set('isCreating', false)" wire:loading.attr="disabled">@lang('admin/api_keys.crud.cancel')</button>
                </div>
            </form>
        </div>
    @endif
</div>
