<div class="media @if ($depth > 0) ml-5 @endif" style="align-items: flex-start;">
    <div class="media-left">
        <div class="image is-32x32 is-round"
            style="background-image: url(/storage/avatars/{{ $comment->user?->avatar ?? App\Models\Setting::get('default_user_avatar') }});"></div>
    </div>
    <div class="media-content">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: .2rem;">
            <div>
                <strong>{{ $comment->user?->name }}</strong>
                <small class="has-text-grey ml-2">{{ $comment->created_at->format('Y-m-d H:i') }}</small>
            </div>
            <div style="display: flex; gap: .25rem;">
                <span class="button is-small is-static @if ($comment->likes->count() > 0) is-success @endif">
                    <span class="icon is-small">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="height:1em;width:1em;fill:currentColor;">
                            <path d="M5,9V21H1V9H5M9,21A2,2 0 0,1 7,19V9C7,8.45 7.22,7.95 7.59,7.59L14.17,1L15.23,2.06C15.5,2.33 15.67,2.7 15.67,3.11L15.64,3.43L14.69,8H21C22.11,8 23,8.9 23,10V12C23,12.26 22.95,12.5 22.86,12.73L19.84,19.78C19.54,20.5 18.83,21 18,21H9M9,19H18.03L21,12V10H12.21L13.34,4.68L9,9.03V19Z" />
                        </svg>
                    </span>
                    @if ($comment->likes->count() > 0)
                        <span style="font-weight: 600;">{{ $comment->likes->count() }}</span>
                    @endif
                </span>
                <span class="button is-small is-static @if ($comment->dislikes->count() > 0) is-danger @endif">
                    <span class="icon is-small">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="height:1em;width:1em;fill:currentColor;">
                            <path d="M19,15V3H23V15H19M15,3A2,2 0 0,1 17,5V15C17,15.55 16.78,16.05 16.41,16.41L9.83,23L8.77,21.94C8.5,21.67 8.33,21.3 8.33,20.88L8.36,20.57L9.31,16H3C1.89,16 1,15.1 1,14V12C1,11.74 1.05,11.5 1.14,11.27L4.16,4.22C4.46,3.5 5.17,3 6,3H15M15,5H5.97L3,12V14H11.78L10.65,19.32L15,14.97V5Z" />
                        </svg>
                    </span>
                    @if ($comment->dislikes->count() > 0)
                        <span style="font-weight: 600;">{{ $comment->dislikes->count() }}</span>
                    @endif
                </span>
            </div>
        </div>
        <p style="margin: 0 0 .25rem; white-space: pre-line;">{{ $comment->body }}</p>
        @foreach ($comment->replies as $reply)
            @include('livewire.admin.posts._comment', ['comment' => $reply, 'depth' => $depth + 1])
        @endforeach
    </div>
</div>
