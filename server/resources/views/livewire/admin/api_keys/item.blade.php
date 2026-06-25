<div class="column is-one-third">
    <div class="card">
        <div class="card-content content">
            <h4>
                {{ $apiKey->name }}
                @if (!$apiKey->active)
                    <span class="tag is-warning is-pulled-right is-hidden-touch">{{ Str::upper(__('admin/api_keys.item.inactive')) }}</span>
                @endif
            </h4>

            @if (!$apiKey->active)
                <p class="is-display-touch is-hidden-desktop">
                    <span class="tag is-warning">{{ Str::upper(__('admin/api_keys.item.inactive')) }}</span>
                </p>
            @endif

            <p>@lang('admin/api_keys.item.key'): <code>{{ $apiKey->key }}</code></p>
            <p>@lang('admin/api_keys.item.requests'): <b>{{ $apiKey->requests }}</b></p>
        </div>

        <div class="card-footer">
            <a class="card-footer-item" wire:click.prevent="$set('isEditing', true)">@lang('admin/api_keys.item.edit')</a>
            <a class="card-footer-item has-text-danger" wire:click.prevent="$set('isDeleting', true)">@lang('admin/api_keys.item.delete')</a>
        </div>
    </div>

    @if ($isEditing)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isEditing', false)"></div>

            <form wire:submit.prevent="editApiKey" class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/api_keys.item.edit_api_key')</p>
                    <button type="button" class="delete" wire:click="$set('isEditing', false)"></button>
                </div>

                <div class="modal-card-body">
                    <div class="field">
                        <label class="label" for="name">@lang('admin/api_keys.item.name')</label>
                        <div class="control">
                            <input class="input @error('apiKey.name') is-danger @enderror" type="text" id="name"
                                wire:model="apiKey.name" required>
                        </div>
                        @error('apiKey.name') <p class="help is-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="active">@lang('admin/api_keys.item.active')</label>
                        <label class="checkbox" for="active">
                            <input type="checkbox" id="active" wire:model="apiKey.active">
                            @lang('admin/api_keys.item.active_api_key')
                        </label>
                    </div>
                </div>

                <div class="modal-card-foot">
                    <button type="submit" class="button is-link">@lang('admin/api_keys.item.edit_api_key')</button>
                    <button type="button" class="button" wire:click="$set('isEditing', false)" wire:loading.attr="disabled">@lang('admin/api_keys.item.cancel')</button>
                </div>
            </form>
        </div>
    @endif

    @if ($isDeleting)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isDeleting', false)"></div>

            <div class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/api_keys.item.delete_api_key')</p>
                    <button type="button" class="delete" wire:click="$set('isDeleting', false)"></button>
                </div>

                <div class="modal-card-body">
                    <p>@lang('admin/api_keys.item.delete_description')</p>
                </div>

                <div class="modal-card-foot">
                    <button class="button is-danger" wire:click="deleteApiKey()" wire:loading.attr="disabled">@lang('admin/api_keys.item.delete_api_key')</button>
                    <button class="button" wire:click="$set('isDeleting', false)" wire:loading.attr="disabled">@lang('admin/api_keys.item.cancel')</button>
                </div>
            </div>
        </div>
    @endif
</div>
