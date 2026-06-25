<div class="column is-one-quarter">
    <div class="card">
        <div class="card-image">
            <div class="image is-square" style="background-image: url({{ asset('storage/game-assets/' . $asset->image_path) }});"></div>
        </div>

        <div class="card-content content">
            <h4 style="font-weight: 600;">{{ $asset->name }}</h4>
            <p class="has-text-grey is-size-7">{{ $asset->created_at->format('d-m-Y') }}</p>
        </div>

        <div class="card-footer">
            <a class="card-footer-item has-text-danger" wire:click.prevent="$set('isDeleting', true)">@lang('admin/games.item.delete')</a>
        </div>
    </div>

    @if ($isDeleting)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isDeleting', false)"></div>

            <div class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/games.item.delete_face')</p>
                    <button type="button" class="delete" wire:click="$set('isDeleting', false)"></button>
                </div>

                <div class="modal-card-body">
                    <p>@lang('admin/games.item.delete_description')</p>
                </div>

                <div class="modal-card-foot">
                    <button class="button is-danger" wire:click="deleteAsset()" wire:loading.attr="disabled">@lang('admin/games.item.delete_face')</button>
                    <button class="button" wire:click="$set('isDeleting', false)" wire:loading.attr="disabled">@lang('admin/games.item.cancel')</button>
                </div>
            </div>
        </div>
    @endif
</div>
