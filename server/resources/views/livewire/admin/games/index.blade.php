<div class="container">
    <h2 class="title">@lang('admin/games.crud.header')</h2>

    <x-search-header :itemName="__('admin/games.crud.faces')">
        <div class="buttons">
            <button class="button is-link" wire:click="$set('isCreating', true)" wire:loading.attr="disabled">@lang('admin/games.crud.create_face')</button>
        </div>

        <x-slot name="sorters">
            <option value="">@lang('admin/games.crud.created_at_desc')</option>
            <option value="created_at">@lang('admin/games.crud.created_at_asc')</option>
            <option value="name">@lang('admin/games.crud.name_asc')</option>
            <option value="name_desc">@lang('admin/games.crud.name_desc')</option>
        </x-slot>
    </x-search-header>

    @if ($assets->count() > 0)
        {{ $assets->links() }}

        <div class="columns is-multiline">
            @foreach ($assets as $asset)
                <livewire:admin.games.item :asset="$asset" wire:key="asset-{{ $asset->id }}" />
            @endforeach
        </div>

        {{ $assets->links() }}
    @else
        <p><i>@lang('admin/games.crud.empty')</i></p>
    @endif

    @if ($isCreating)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isCreating', false)"></div>

            <form wire:submit.prevent="createAsset" class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/games.crud.create_face')</p>
                    <button type="button" class="delete" wire:click="$set('isCreating', false)"></button>
                </div>

                <div class="modal-card-body">
                    <div class="field">
                        <label class="label" for="name">@lang('admin/games.crud.name')</label>
                        <div class="control">
                            <input class="input @error('name') is-danger @enderror"
                                type="text"
                                id="name"
                                wire:model="name"
                                required>
                        </div>
                        @error('name') <p class="help is-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="image">@lang('admin/games.crud.image')</label>
                        <div class="control">
                            <input class="input @error('image') is-danger @enderror"
                                type="file"
                                id="image"
                                accept=".jpg,.jpeg,.png"
                                wire:model="image">
                        </div>
                        @error('image')
                            <p class="help is-danger">{{ $message }}</p>
                        @else
                            <p class="help">@lang('admin/games.crud.image_help')</p>
                        @enderror
                    </div>
                </div>

                <div class="modal-card-foot">
                    <button type="submit" class="button is-link" wire:loading.attr="disabled">@lang('admin/games.crud.create_face')</button>
                    <button type="button" class="button" wire:click="$set('isCreating', false)" wire:loading.attr="disabled">@lang('admin/games.crud.cancel')</button>
                </div>
            </form>
        </div>
    @endif
</div>
