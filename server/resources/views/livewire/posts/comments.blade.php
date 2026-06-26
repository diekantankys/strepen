<div class="box mt-4">
    <h5 class="title is-5">@lang('posts.comments.title')</h5>

    @forelse ($post->comments as $comment)
        @include('livewire.posts.comment-item', ['comment' => $comment, 'depth' => 0])
    @empty
        <p><i>@lang('posts.comments.empty')</i></p>
    @endforelse

    @if ($deletingCommentId)
        <div class="modal is-active">
            <div class="modal-background" wire:click="cancelDeleteComment"></div>
            <div class="modal-card">
                <div class="modal-card-head">
                    <p class="modal-card-title">@lang('posts.comments.delete')</p>
                    <button type="button" class="delete" wire:click="cancelDeleteComment"></button>
                </div>
                <div class="modal-card-body">
                    <p>@lang('posts.comments.confirm_delete')</p>
                </div>
                <div class="modal-card-foot">
                    <button class="button is-danger" wire:click="confirmDeleteComment" wire:loading.attr="disabled">
                        @lang('posts.comments.delete')
                    </button>
                    <button class="button" wire:click="cancelDeleteComment" wire:loading.attr="disabled">
                        @lang('posts.comments.cancel')
                    </button>
                </div>
            </div>
        </div>
    @endif

    @auth
        @if (Auth::id() != 1)
            <hr>

            <h6 class="title is-6">@lang('posts.comments.add_comment')</h6>

            <div class="field">
                <div class="control">
                    <textarea class="textarea" wire:model="body" rows="3"
                        placeholder="@lang('posts.comments.placeholder')"></textarea>
                </div>
                @error('body')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="control">
                <button class="button is-link" wire:click="submitComment">
                    @lang('posts.comments.submit')
                </button>
            </div>
        @endif
    @endauth
</div>
