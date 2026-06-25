<div class="card @if (!$standalone) my-5 @endif" style="overflow: hidden;">
    @if ($post->image != null)
        <div class="card-image">
            <div class="image is-widescreen" style="background-image: url(/storage/posts/{{ $post->image }});"></div>
        </div>
    @endif

    <div class="card-content content">
        <h4>
            <a href="{{ route('posts.show', $post) }}" style="color: inherit;" wire:navigate>{{ $post->title }}</a>

            <div class="buttons is-pulled-right is-hidden-touch">
                <button class="button @if ($post->likes->contains(Auth::user())) is-success @endif" wire:click="likePost">
                    <svg xmlns="http://www.w3.org/2000/svg" class="image is-small is-inline" viewBox="0 0 24 24">
                        @if ($post->likes->contains(Auth::user()))
                            <path d="M23,10C23,8.89 22.1,8 21,8H14.68L15.64,3.43C15.66,3.33 15.67,3.22 15.67,3.11C15.67,2.7 15.5,2.32 15.23,2.05L14.17,1L7.59,7.58C7.22,7.95 7,8.45 7,9V19A2,2 0 0,0 9,21H18C18.83,21 19.54,20.5 19.84,19.78L22.86,12.73C22.95,12.5 23,12.26 23,12V10M1,21H5V9H1V21Z" />
                        @else
                            <path d="M5,9V21H1V9H5M9,21A2,2 0 0,1 7,19V9C7,8.45 7.22,7.95 7.59,7.59L14.17,1L15.23,2.06C15.5,2.33 15.67,2.7 15.67,3.11L15.64,3.43L14.69,8H21C22.11,8 23,8.9 23,10V12C23,12.26 22.95,12.5 22.86,12.73L19.84,19.78C19.54,20.5 18.83,21 18,21H9M9,19H18.03L21,12V10H12.21L13.34,4.68L9,9.03V19Z" />
                        @endif
                    </svg>
                    @if ($post->likes->count() > 0)
                        <span style="font-weight: 600;">{{ $post->likes->count() }}</span>
                    @else
                        @lang('posts.item.like')
                    @endif
                </button>

                <button class="button @if ($post->dislikes->contains(Auth::user())) is-danger @endif" wire:click="dislikePost">
                    <svg xmlns="http://www.w3.org/2000/svg" class="image is-small is-inline" viewBox="0 0 24 24">
                        @if ($post->dislikes->contains(Auth::user()))
                            <path d="M19,15H23V3H19M15,3H6C5.17,3 4.46,3.5 4.16,4.22L1.14,11.27C1.05,11.5 1,11.74 1,12V14A2,2 0 0,0 3,16H9.31L8.36,20.57C8.34,20.67 8.33,20.77 8.33,20.88C8.33,21.3 8.5,21.67 8.77,21.94L9.83,23L16.41,16.41C16.78,16.05 17,15.55 17,15V5C17,3.89 16.1,3 15,3Z" />
                        @else
                            <path d="M19,15V3H23V15H19M15,3A2,2 0 0,1 17,5V15C17,15.55 16.78,16.05 16.41,16.41L9.83,23L8.77,21.94C8.5,21.67 8.33,21.3 8.33,20.88L8.36,20.57L9.31,16H3C1.89,16 1,15.1 1,14V12C1,11.74 1.05,11.5 1.14,11.27L4.16,4.22C4.46,3.5 5.17,3 6,3H15M15,5H5.97L3,12V14H11.78L10.65,19.32L15,14.97V5Z" />
                        @endif
                    </svg>
                    @if ($post->dislikes->count() > 0)
                        <span style="font-weight: 600;">{{ $post->dislikes->count() }}</span>
                    @else
                        @lang('posts.item.dislike')
                    @endif
                </button>
            </div>
        </h4>

        <p><i>@lang('posts.item.written_by', ['user.name' => $post->user->name, 'post.created_at' => $post->created_at->format('Y-m-d H:i')])</i></p>

        {!! App\Helpers\BetterParsedown::instance()->text($post->body) !!}

        <div class="buttons is-display-touch is-hidden-desktop">
            <button class="button @if ($post->likes->contains(Auth::user())) is-success @endif" style="flex: 1;" wire:click="likePost">
                <svg xmlns="http://www.w3.org/2000/svg" class="image is-small is-inline" viewBox="0 0 24 24">
                    @if ($post->likes->contains(Auth::user()))
                        <path d="M23,10C23,8.89 22.1,8 21,8H14.68L15.64,3.43C15.66,3.33 15.67,3.22 15.67,3.11C15.67,2.7 15.5,2.32 15.23,2.05L14.17,1L7.59,7.58C7.22,7.95 7,8.45 7,9V19A2,2 0 0,0 9,21H18C18.83,21 19.54,20.5 19.84,19.78L22.86,12.73C22.95,12.5 23,12.26 23,12V10M1,21H5V9H1V21Z" />
                    @else
                        <path d="M5,9V21H1V9H5M9,21A2,2 0 0,1 7,19V9C7,8.45 7.22,7.95 7.59,7.59L14.17,1L15.23,2.06C15.5,2.33 15.67,2.7 15.67,3.11L15.64,3.43L14.69,8H21C22.11,8 23,8.9 23,10V12C23,12.26 22.95,12.5 22.86,12.73L19.84,19.78C19.54,20.5 18.83,21 18,21H9M9,19H18.03L21,12V10H12.21L13.34,4.68L9,9.03V19Z" />
                    @endif
                </svg>
                @if ($post->likes->count() > 0)
                    <span style="font-weight: 600;">{{ $post->likes->count() }}</span>
                @else
                    @lang('posts.item.like')
                @endif
            </button>

            <button class="button @if ($post->dislikes->contains(Auth::user())) is-danger @endif" style="flex: 1;" wire:click="dislikePost">
                <svg xmlns="http://www.w3.org/2000/svg" class="image is-small is-inline" viewBox="0 0 24 24">
                    @if ($post->dislikes->contains(Auth::user()))
                        <path d="M19,15H23V3H19M15,3H6C5.17,3 4.46,3.5 4.16,4.22L1.14,11.27C1.05,11.5 1,11.74 1,12V14A2,2 0 0,0 3,16H9.31L8.36,20.57C8.34,20.67 8.33,20.77 8.33,20.88C8.33,21.3 8.5,21.67 8.77,21.94L9.83,23L16.41,16.41C16.78,16.05 17,15.55 17,15V5C17,3.89 16.1,3 15,3Z" />
                    @else
                        <path d="M19,15V3H23V15H19M15,3A2,2 0 0,1 17,5V15C17,15.55 16.78,16.05 16.41,16.41L9.83,23L8.77,21.94C8.5,21.67 8.33,21.3 8.33,20.88L8.36,20.57L9.31,16H3C1.89,16 1,15.1 1,14V12C1,11.74 1.05,11.5 1.14,11.27L4.16,4.22C4.46,3.5 5.17,3 6,3H15M15,5H5.97L3,12V14H11.78L10.65,19.32L15,14.97V5Z" />
                    @endif
                </svg>
                @if ($post->dislikes->count() > 0)
                    <span style="font-weight: 600;">{{ $post->dislikes->count() }}</span>
                @else
                    @lang('posts.item.dislike')
                @endif
            </button>
        </div>
    </div>
</div>
