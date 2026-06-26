<div class="container is-max-desktop">
    <livewire:posts.item :post="$post" standalone="true" />

    <livewire:posts.comments :post="$post" />

    <div class="buttons mt-5 is-centered">
        <a class="button is-link" href="{{ route('home') }}" wire:navigate>@lang('posts.show.go_back')</a>
    </div>
</div>
