<?php

namespace App\Http\Livewire\Posts;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Item extends Component
{
    public $post;

    public $standalone = false;

    public function likePost()
    {
        $this->post->like(Auth::user());
    }

    public function dislikePost()
    {
        $this->post->dislike(Auth::user());
    }

    public function render()
    {
        unset($this->post->likes);
        unset($this->post->dislikes);

        return view('livewire.posts.item');
    }
}
