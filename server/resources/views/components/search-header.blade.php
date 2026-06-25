<div class="columns">
    <div class="column is-one-third">
        {{ $slot }}
    </div>
    <div class="column is-two-thirds">
        <form wire:submit.prevent="search">
            @if (isset($sorters))
            <div class="columns">
                    <div class="column is-one-quarter">
                        <div class="field">
                            <div class="control" style="width: 100%;">
                                <div class="select is-fullwidth">
                                    <select wire:model="sort_by">
                                        {{ $sorters }}
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                <div class="column is-three-quarters">
            @endif
                    <div class="field has-addons is-block-mobile">
                        <div class="control" style="width: 100%;">
                            <input class="input" type="text" wire:model="query" placeholder="@lang('components.search_header.query', ['item.name' => $itemName])">
                        </div>
                        @if (isset($filters))
                            {{ $filters }}
                        @endif
                        <div class="control">
                            <button class="button is-link" type="submit" style="width: 100%;">@lang('components.search_header.search')</button>
                        </div>
                    </div>
            @if (isset($sorters))
                </div>
            </div>
            @endif
        </form>
    </div>
</div>
