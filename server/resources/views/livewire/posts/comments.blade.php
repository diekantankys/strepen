<div class="box mt-4">
    <h5 class="title is-5">@lang('posts.comments.title')</h5>

    @forelse ($post->comments as $comment)
        @include('livewire.posts._comment', ['comment' => $comment, 'depth' => 0])
    @empty
        <p><i>@lang('posts.comments.empty')</i></p>
    @endforelse

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
