<article class="media @if ($depth > 0) ml-5 @endif">
    <figure class="media-left">
        <div class="image is-48x48 is-round"
            style="background-image: url(/storage/avatars/{{ $comment->user?->avatar ?? App\Models\Setting::get('default_user_avatar') }});"></div>
    </figure>

    <div class="media-content">
        <div class="content" style="margin-bottom: .5rem;">
            <p style="margin-bottom: .25rem;">
                <strong>{{ $comment->user?->name }}</strong>
                <small class="has-text-grey ml-2">{{ $comment->created_at->format('Y-m-d H:i') }}</small>

                <span class="buttons is-pulled-right is-hidden-touch">
                    <button class="button is-small @if ($comment->likes->contains(Auth::user())) is-success @endif"
                        wire:click="likeComment({{ $comment->id }})">
                        <span class="icon is-small">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="height:1em;width:1em;fill:currentColor;">
                                @if ($comment->likes->contains(Auth::user()))
                                    <path d="M23,10C23,8.89 22.1,8 21,8H14.68L15.64,3.43C15.66,3.33 15.67,3.22 15.67,3.11C15.67,2.7 15.5,2.32 15.23,2.05L14.17,1L7.59,7.58C7.22,7.95 7,8.45 7,9V19A2,2 0 0,0 9,21H18C18.83,21 19.54,20.5 19.84,19.78L22.86,12.73C22.95,12.5 23,12.26 23,12V10M1,21H5V9H1V21Z" />
                                @else
                                    <path d="M5,9V21H1V9H5M9,21A2,2 0 0,1 7,19V9C7,8.45 7.22,7.95 7.59,7.59L14.17,1L15.23,2.06C15.5,2.33 15.67,2.7 15.67,3.11L15.64,3.43L14.69,8H21C22.11,8 23,8.9 23,10V12C23,12.26 22.95,12.5 22.86,12.73L19.84,19.78C19.54,20.5 18.83,21 18,21H9M9,19H18.03L21,12V10H12.21L13.34,4.68L9,9.03V19Z" />
                                @endif
                            </svg>
                        </span>
                        @if ($comment->likes->count() > 0)
                            <span style="font-weight: 600;">{{ $comment->likes->count() }}</span>
                        @endif
                    </button>

                    <button class="button is-small @if ($comment->dislikes->contains(Auth::user())) is-danger @endif"
                        wire:click="dislikeComment({{ $comment->id }})">
                        <span class="icon is-small">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="height:1em;width:1em;fill:currentColor;">
                                @if ($comment->dislikes->contains(Auth::user()))
                                    <path d="M19,15H23V3H19M15,3H6C5.17,3 4.46,3.5 4.16,4.22L1.14,11.27C1.05,11.5 1,11.74 1,12V14A2,2 0 0,0 3,16H9.31L8.36,20.57C8.34,20.67 8.33,20.77 8.33,20.88C8.33,21.3 8.5,21.67 8.77,21.94L9.83,23L16.41,16.41C16.78,16.05 17,15.55 17,15V5C17,3.89 16.1,3 15,3Z" />
                                @else
                                    <path d="M19,15V3H23V15H19M15,3A2,2 0 0,1 17,5V15C17,15.55 16.78,16.05 16.41,16.41L9.83,23L8.77,21.94C8.5,21.67 8.33,21.3 8.33,20.88L8.36,20.57L9.31,16H3C1.89,16 1,15.1 1,14V12C1,11.74 1.05,11.5 1.14,11.27L4.16,4.22C4.46,3.5 5.17,3 6,3H15M15,5H5.97L3,12V14H11.78L10.65,19.32L15,14.97V5Z" />
                                @endif
                            </svg>
                        </span>
                        @if ($comment->dislikes->count() > 0)
                            <span style="font-weight: 600;">{{ $comment->dislikes->count() }}</span>
                        @endif
                    </button>
                </span>
            </p>
            <p style="margin: 0; white-space: pre-line;">{{ $comment->body }}</p>
        </div>

        <div class="buttons is-display-touch is-hidden-desktop is-justify-content-flex-end">
            <button class="button is-small @if ($comment->likes->contains(Auth::user())) is-success @endif"
                wire:click="likeComment({{ $comment->id }})">
                <span class="icon is-small">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="height:1em;width:1em;fill:currentColor;">
                        @if ($comment->likes->contains(Auth::user()))
                            <path d="M23,10C23,8.89 22.1,8 21,8H14.68L15.64,3.43C15.66,3.33 15.67,3.22 15.67,3.11C15.67,2.7 15.5,2.32 15.23,2.05L14.17,1L7.59,7.58C7.22,7.95 7,8.45 7,9V19A2,2 0 0,0 9,21H18C18.83,21 19.54,20.5 19.84,19.78L22.86,12.73C22.95,12.5 23,12.26 23,12V10M1,21H5V9H1V21Z" />
                        @else
                            <path d="M5,9V21H1V9H5M9,21A2,2 0 0,1 7,19V9C7,8.45 7.22,7.95 7.59,7.59L14.17,1L15.23,2.06C15.5,2.33 15.67,2.7 15.67,3.11L15.64,3.43L14.69,8H21C22.11,8 23,8.9 23,10V12C23,12.26 22.95,12.5 22.86,12.73L19.84,19.78C19.54,20.5 18.83,21 18,21H9M9,19H18.03L21,12V10H12.21L13.34,4.68L9,9.03V19Z" />
                        @endif
                    </svg>
                </span>
                @if ($comment->likes->count() > 0)
                    <span style="font-weight: 600;">{{ $comment->likes->count() }}</span>
                @endif
            </button>

            <button class="button is-small @if ($comment->dislikes->contains(Auth::user())) is-danger @endif"
                wire:click="dislikeComment({{ $comment->id }})">
                <span class="icon is-small">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="height:1em;width:1em;fill:currentColor;">
                        @if ($comment->dislikes->contains(Auth::user()))
                            <path d="M19,15H23V3H19M15,3H6C5.17,3 4.46,3.5 4.16,4.22L1.14,11.27C1.05,11.5 1,11.74 1,12V14A2,2 0 0,0 3,16H9.31L8.36,20.57C8.34,20.67 8.33,20.77 8.33,20.88C8.33,21.3 8.5,21.67 8.77,21.94L9.83,23L16.41,16.41C16.78,16.05 17,15.55 17,15V5C17,3.89 16.1,3 15,3Z" />
                        @else
                            <path d="M19,15V3H23V15H19M15,3A2,2 0 0,1 17,5V15C17,15.55 16.78,16.05 16.41,16.41L9.83,23L8.77,21.94C8.5,21.67 8.33,21.3 8.33,20.88L8.36,20.57L9.31,16H3C1.89,16 1,15.1 1,14V12C1,11.74 1.05,11.5 1.14,11.27L4.16,4.22C4.46,3.5 5.17,3 6,3H15M15,5H5.97L3,12V14H11.78L10.65,19.32L15,14.97V5Z" />
                        @endif
                    </svg>
                </span>
                @if ($comment->dislikes->count() > 0)
                    <span style="font-weight: 600;">{{ $comment->dislikes->count() }}</span>
                @endif
            </button>
        </div>

        <nav class="level is-mobile">
            <div class="level-left">
                @auth
                    @if (Auth::id() != 1 && $depth < 3)
                        <button class="button is-small is-link level-item"
                            wire:click="startReply({{ $comment->id }})">
                            @lang('posts.comments.reply')
                        </button>
                    @endif

                    @if (Auth::user()->admin || (Auth::id() == $comment->user_id && $comment->replies->isEmpty()))
                        <button class="button is-small is-danger level-item"
                            wire:click="requestDeleteComment({{ $comment->id }})">
                            @lang('posts.comments.delete')
                        </button>
                    @endif
                @endauth
            </div>
        </nav>

        @if ($replyingToId == $comment->id)
            <div class="field mt-2">
                <div class="control">
                    <textarea class="textarea is-small" wire:model="replyBody" rows="2"
                        placeholder="@lang('posts.comments.placeholder')"></textarea>
                </div>
                @error('replyBody')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="buttons">
                <button class="button is-link is-small" wire:click="submitReply">
                    @lang('posts.comments.submit')
                </button>
                <button class="button is-small" wire:click="cancelReply">
                    @lang('posts.comments.cancel')
                </button>
            </div>
        @endif

        @foreach ($comment->replies as $reply)
            @include('livewire.posts.comment-item', ['comment' => $reply, 'depth' => $depth + 1])
        @endforeach
    </div>
</article>
