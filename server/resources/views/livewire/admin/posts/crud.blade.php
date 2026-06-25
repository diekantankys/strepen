<div class="container">
    <h2 class="title">@lang('admin/posts.crud.header')</h2>

    <x-search-header :itemName="__('admin/posts.crud.posts')">
        <div class="buttons">
            <button class="button is-link" wire:click="$set('isCreating', true)" wire:loading.attr="disabled">@lang('admin/posts.crud.create_post')</button>
        </div>

        <x-slot name="sorters">
            <option value="">@lang('admin/posts.crud.created_at_desc')</option>
            <option value="created_at">@lang('admin/posts.crud.created_at_asc')</option>
            <option value="title">@lang('admin/posts.crud.title_asc')</option>
            <option value="title_desc">@lang('admin/posts.crud.title_desc')</option>
        </x-slot>

        <x-slot name="filters">
            <livewire:components.user-chooser name="user_filter" :userId="$user_id" includeInactive="true" inline="true" relationship="true" postsRequired="true" wire:key="admin-posts-filter-user-chooser" />
        </x-slot>
    </x-search-header>

    @if ($posts->count() > 0)
        {{ $posts->links() }}

        <div class="columns is-multiline">
            @foreach ($posts as $post)
                <livewire:admin.posts.item :post="$post" wire:key="post-{{ $post->id }}" />
            @endforeach
        </div>

        {{ $posts->links() }}
    @else
        <p><i>@lang('admin/posts.crud.empty')</i></p>
    @endif

    @if ($isCreating)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isCreating', false)"></div>

            <form wire:submit.prevent="createPost" class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/posts.crud.create_post')</p>
                    <button type="button" class="delete" wire:click="$set('isCreating', false)"></button>
                </div>

                <div class="modal-card-body">
                    <div class="field">
                        <label class="label" for="title">@lang('admin/posts.crud._title')</label>
                        <div class="control">
                            <input class="input @error('post.title') is-danger @enderror" type="text" id="title"
                                wire:model="post.title" required>
                        </div>
                        @error('post.title') <p class="help is-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="image">@lang('admin/posts.crud.image')</label>
                        <div class="control">
                            <input class="input @error('image') is-danger @enderror" type="file" accept=".jpg,.jpeg,.png"
                                id="image" wire:model="image">
                        </div>
                        @error('image')
                            <p class="help is-danger">{{ $message }}</p>
                        @else
                            <p class="help">@lang('admin/posts.crud.image_help')</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="body">@lang('admin/posts.crud.body', ['markdown_link' => '<a href="https://en.wikipedia.org/wiki/Markdown#Examples" target="_blank" rel="noreferrer">Markdown</a>'])</label>
                        <div class="control">
                            <textarea class="textarea is-family-monospace has-fixed-size @error('post.body') is-danger @enderror" id="body"
                                wire:model="post.body" rows="12" required></textarea>
                        </div>
                        @error('post.body') <p class="help is-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="column">
                        <div class="field">
                            <label class="label" for="send_notification">@lang('admin/posts.crud.send_notification')</label>
                            <label class="checkbox" for="send_notification">
                                <input type="checkbox" id="send_notification" wire:model="sendNotification">
                                @lang('admin/posts.crud.send_notification_details')
                            </label>
                        </div>
                    </div>
                </div>

                <div class="modal-card-foot">
                    <button type="submit" class="button is-link">@lang('admin/posts.crud.create_post')</button>
                    <button type="button" class="button" wire:click="$set('isCreating', false)" wire:loading.attr="disabled">@lang('admin/posts.crud.cancel')</button>
                </div>
            </form>
        </div>
    @endif
</div>
