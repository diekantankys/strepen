<div class="container">
    <h2 class="title">@lang('admin/post_comments.crud.header')</h2>

    <x-search-header :itemName="__('admin/post_comments.crud.comments')">
        <x-slot name="sorters">
            <option value="">@lang('admin/post_comments.crud.created_at_desc')</option>
            <option value="created_at">@lang('admin/post_comments.crud.created_at_asc')</option>
        </x-slot>
    </x-search-header>

    @if ($comments->count() > 0)
        {{ $comments->links() }}

        <div class="columns is-multiline">
            @foreach ($comments as $comment)
                <livewire:admin.post-comments.item :comment="$comment" wire:key="comment-{{ $comment->id }}" />
            @endforeach
        </div>

        {{ $comments->links() }}
    @else
        <p><i>@lang('admin/post_comments.crud.empty')</i></p>
    @endif
</div>
