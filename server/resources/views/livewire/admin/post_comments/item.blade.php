<div class="column is-one-third">
    <div class="card">
        <div class="card-content content">
            <h4>
                @if ($comment->post)
                    <a href="{{ route('posts.show', $comment->post) }}" wire:navigate>{{ $comment->post->title }}</a>
                @else
                    @lang('admin/post_comments.item.missing_post')
                @endif
            </h4>
            <p><i>@lang('admin/post_comments.item.written_by', ['user.name' => $comment->user?->name ?? '?', 'comment.created_at' => $comment->created_at->format('Y-m-d H:i')])</i></p>
            <p>{{ Str::limit($comment->body, 200) }}</p>
        </div>

        <div class="card-footer">
            <a class="card-footer-item" wire:click.prevent="$set('isShowing', true)">@lang('admin/post_comments.item.show')</a>
            <a class="card-footer-item" wire:click.prevent="$set('isEditing', true)">@lang('admin/post_comments.item.edit')</a>
            <a class="card-footer-item has-text-danger" wire:click.prevent="$set('isDeleting', true)">@lang('admin/post_comments.item.delete')</a>
        </div>
    </div>

    @if ($isShowing)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isShowing', false)"></div>

            <div class="modal-card is-large">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/post_comments.item.show_comment')</p>
                    <button type="button" class="delete" wire:click="$set('isShowing', false)"></button>
                </div>

                <div class="modal-card-body content">
                    <div class="columns">
                        <div class="column is-half">
                            <p><b>@lang('admin/post_comments.item.post'):</b>
                                @if ($comment->post)
                                    <a href="{{ route('posts.show', $comment->post) }}" wire:navigate>{{ $comment->post->title }}</a>
                                @else
                                    @lang('admin/post_comments.item.missing_post')
                                @endif
                            </p>
                            <p><i>@lang('admin/post_comments.item.written_by', ['user.name' => $comment->user?->name ?? '?', 'comment.created_at' => $comment->created_at->format('Y-m-d H:i')])</i></p>
                            @if ($comment->parent_id)
                                <p class="has-text-grey is-size-7">@lang('admin/post_comments.item.reply_to', ['id' => $comment->parent_id])</p>
                            @endif
                            <div class="box">{{ $comment->body }}</div>
                        </div>

                        <div class="column is-half">
                            <h4>@lang('admin/post_comments.item.likes') (<x-amount-format :amount="$comment->likes->count()" />)</h4>
                            @forelse ($comment->likes as $user)
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
                                <p><i>@lang('admin/post_comments.item.likes_empty')</i></p>
                            @endforelse

                            <h4 class="mt-6">@lang('admin/post_comments.item.dislikes') (<x-amount-format :amount="$comment->dislikes->count()" />)</h4>
                            @forelse ($comment->dislikes as $user)
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
                                <p><i>@lang('admin/post_comments.item.dislikes_empty')</i></p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($isEditing)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isEditing', false)"></div>

            <form wire:submit.prevent="editComment" class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/post_comments.item.edit_comment')</p>
                    <button type="button" class="delete" wire:click="$set('isEditing', false)"></button>
                </div>

                <div class="modal-card-body">
                    <div class="field">
                        <label class="label">@lang('admin/post_comments.item.body')</label>
                        <div class="control">
                            <textarea class="textarea @error('comment.body') is-danger @enderror"
                                wire:model="comment.body" rows="5" required></textarea>
                        </div>
                        @error('comment.body') <p class="help is-danger">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="modal-card-foot">
                    <button type="submit" class="button is-link">@lang('admin/post_comments.item.edit_comment')</button>
                    <button type="button" class="button" wire:click="$set('isEditing', false)" wire:loading.attr="disabled">@lang('admin/post_comments.item.cancel')</button>
                </div>
            </form>
        </div>
    @endif

    @if ($isDeleting)
        <div class="modal is-active">
            <div class="modal-background" wire:click="$set('isDeleting', false)"></div>

            <div class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('admin/post_comments.item.delete_comment')</p>
                    <button type="button" class="delete" wire:click="$set('isDeleting', false)"></button>
                </div>

                <div class="modal-card-body">
                    <p>@lang('admin/post_comments.item.delete_description')</p>
                </div>

                <div class="modal-card-foot">
                    <button class="button is-danger" wire:click="deleteComment()" wire:loading.attr="disabled">@lang('admin/post_comments.item.delete_comment')</button>
                    <button class="button" wire:click="$set('isDeleting', false)" wire:loading.attr="disabled">@lang('admin/post_comments.item.cancel')</button>
                </div>
            </div>
        </div>
    @endif
</div>
