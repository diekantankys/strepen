<div class="column is-one-third">
    <div class="card">
        @if ($post->image != null)
            <div class="card-image">
                <div class="image is-widescreen" style="background-image: url(/storage/posts/{{ $post->image }});"></div>
            </div>
        @endif

        <div class="card-content content">
            <h4>{{ $post->title }}</h4>
            <p><i>@lang('admin/posts.item.written_by', ['user.name' => $post->user != null ? $post->user->name : '?', 'post.created_at' => $post->created_at->format('Y-m-d H:i')])</i></p>
            <pre>{{  Str::limit(str_replace(["\r", "\n"], '', $post->body), 240) }}</pre>
        </div>

        <div class="card-footer">
            <a class="card-footer-item" wire:click.prevent="$set('isShowing', true)">@lang('admin/posts.item.show')</a>
            <a class="card-footer-item" wire:click.prevent="$set('isEditing', true)">@lang('admin/posts.item.edit')</a>
            <a class="card-footer-item has-text-danger" wire:click.prevent="$set('isDeleting', true)">@lang('admin/posts.item.delete')</a>
        </div>
    </div>

    @if ($isShowing)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isShowing', false)"></div>

            <div class="modal-card is-large">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/posts.item.show_post')</p>
                    <button type="button" class="delete" wire:click="$set('isShowing', false)"></button>
                </div>

                <div class="modal-card-body content">
                    <div class="columns">
                        <div class="column is-half">
                            @if ($post->image != null)
                                <div class="image mb-5 is-widescreen is-rounded" style="background-image: url(/storage/posts/{{ $post->image }});"></div>
                            @endif
                            <h4>
                                <a href="{{ route('posts.show', $post) }}" style="color: inherit;" wire:navigate>{{ $post->title }}</a>
                            </h4>
                            <p><i>@lang('admin/posts.item.written_by', ['user.name' => $post->user->name, 'post.created_at' => $post->created_at->format('Y-m-d H:i')])</i></p>
                            {!! App\Helpers\BetterParsedown::instance()->text($post->body) !!}
                        </div>

                        <div class="column is-half">
                            <h4>@lang('admin/posts.item.likes') (<x-amount-format :amount="$post->likes->count()" />)</h4>
                            @forelse ($post->likes as $user)
                                <div class="media" style="align-items: center;">
                                    <div class="media-left">
                                        <div class="image is-large is-round" style="background-image: url(/storage/avatars/{{ $user->avatar ?? App\Models\Setting::get('default_user_avatar') }});"></div>
                                    </div>
                                    <div class="media-content">
                                        <p class="mb-0"><b>{{ $user->name }}</b></p>
                                        <p class="has-text-grey" style="font-size:.75rem;">{{ $user->pivot->created_at->format('Y-m-d H:i:s') }}</p>
                                    </div>
                                </div>
                            @empty
                                <p><i>@lang('admin/posts.item.likes_empty')</i></p>
                            @endforelse

                            <h4 class="mt-6">@lang('admin/posts.item.dislikes') (<x-amount-format :amount="$post->dislikes->count()" />)</h4>
                            @forelse ($post->dislikes as $user)
                                <div class="media" style="align-items: center;">
                                    <div class="media-left">
                                        <div class="image is-large is-round" style="background-image: url(/storage/avatars/{{ $user->avatar ?? App\Models\Setting::get('default_user_avatar') }});"></div>
                                    </div>
                                    <div class="media-content">
                                        <p class="mb-0"><b>{{ $user->name }}</b></p>
                                        <p class="has-text-grey" style="font-size:.75rem;">{{ $user->pivot->created_at->format('Y-m-d H:i:s') }}</p>
                                    </div>
                                </div>
                            @empty
                                <p><i>@lang('admin/posts.item.dislikes_empty')</i></p>
                            @endforelse
                        </div>
                    </div>

                    <hr>

                    <h4>@lang('admin/posts.item.comments') (<x-amount-format :amount="$post->comments->count()" />)</h4>
                    @forelse ($post->comments as $comment)
                        @include('livewire.admin.posts._comment', ['comment' => $comment, 'depth' => 0])
                    @empty
                        <p><i>@lang('admin/posts.item.comments_empty')</i></p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    @if ($isEditing)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isEditing', false)"></div>

            <form wire:submit.prevent="editPost" class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/posts.item.edit_post')</p>
                    <button type="button" class="delete" wire:click="$set('isEditing', false)"></button>
                </div>

                <div class="modal-card-body">
                    <livewire:components.user-chooser name="item_user" :userId="$post->user_id" includeInactive="true" :invalid="$this->isInputInvalid('item_user')" wire:key="admin-post-{{ $post->id }}-user-chooser" />

                    <div class="field">
                        <label class="label" for="title">@lang('admin/posts.item.title')</label>
                        <div class="control">
                            <input class="input @error('post.title') is-danger @enderror" type="text" id="title"
                                wire:model="post.title" required>
                        </div>
                        @error('post.title') <p class="help is-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="columns">
                        <div class="column">
                            <div class="field">
                                <label class="label" for="created_at_date">@lang('admin/posts.item.created_at_date')</label>
                                <div class="control">
                                    <input class="input @error('createdAtDate') is-danger @enderror" type="date" id="created_at_date"
                                        wire:model="createdAtDate" required>
                                </div>
                                @error('createdAtDate') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="column">
                            <div class="field">
                                <label class="label" for="created_at_time">@lang('admin/posts.item.created_at_time')</label>
                                <div class="control">
                                    <input class="input @error('createdAtTime') is-danger @enderror" type="time" step="1" id="created_at_time"
                                        wire:model="createdAtTime" required>
                                </div>
                                @error('createdAtTime') <p class="help is-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="field">
                        <label class="label" for="image">@lang('admin/posts.item.image')</label>
                        @if ($post->image != null)
                            <div class="box" style="width: 50%;">
                                <div class="image is-widescreen is-rounded" style="background-image: url(/storage/posts/{{ $post->image }});"></div>
                            </div>
                        @endif
                    </div>

                    <div class="columns">
                        <div class="column">
                            <div class="field">
                                <div class="control">
                                    <input class="input @error('image') is-danger @enderror" type="file" accept=".jpg,.jpeg,.png"
                                        id="image" wire:model="image">
                                </div>
                                @error('image')
                                    <p class="help is-danger">{{ $message }}</p>
                                @else
                                    <p class="help">@lang('admin/posts.item.image_help')</p>
                                @enderror
                            </div>
                        </div>

                        @if ($post->image != null)
                            <div class="column">
                                <div class="field">
                                    <div class="control">
                                        <button type="button" class="button is-danger" wire:click="deleteImage" wire:loading.attr="disabled">@lang('admin/posts.item.delete_image')</button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="field">
                        <label class="label" for="body">@lang('admin/posts.item.body', ['markdown_link' => '<a href="https://en.wikipedia.org/wiki/Markdown#Examples" target="_blank" rel="noreferrer">Markdown</a>'])</label>
                        <div class="control">
                            <textarea class="textarea is-family-monospace has-fixed-size @error('post.body') is-danger @enderror" id="postBody"
                                wire:model="post.body" rows="12" required></textarea>
                        </div>
                        @error('post.body') <p class="help is-danger">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="modal-card-foot">
                    <button type="submit" class="button is-link">@lang('admin/posts.item.edit_post')</button>
                    <button type="button" class="button" wire:click="$set('isEditing', false)" wire:loading.attr="disabled">@lang('admin/posts.item.cancel')</button>
                </div>
            </form>
        </div>
    @endif

    @if ($isDeleting)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isDeleting', false)"></div>

            <div class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/posts.item.delete_post')</p>
                    <button type="button" class="delete" wire:click="$set('isDeleting', false)"></button>
                </div>

                <div class="modal-card-body">
                    <p>@lang('admin/posts.item.delete_description')</p>
                </div>

                <div class="modal-card-foot">
                    <button class="button is-danger" wire:click="deletePost()" wire:loading.attr="disabled">@lang('admin/posts.item.delete_post')</button>
                    <button class="button" wire:click="$set('isDeleting', false)" wire:loading.attr="disabled">@lang('admin/posts.item.cancel')</button>
                </div>
            </div>
        </div>
    @endif
</div>
