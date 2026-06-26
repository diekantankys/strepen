<?php

namespace App\Http\Livewire\Admin\PostComments;

use Livewire\Component;

class Item extends Component
{
    public $comment;

    public $isShowing = false;

    public $isEditing = false;

    public $isDeleting = false;

    public $rules = [
        'comment.body' => 'required|min:1|max:1000',
    ];

    public function editComment(): void
    {
        $this->validate();
        $this->comment->save();
        $this->isEditing = false;
        $this->dispatch('refresh')->to(Crud::class);
    }

    public function deleteComment(): void
    {
        $this->isDeleting = false;
        $this->comment->delete();
        $this->dispatch('refresh')->to(Crud::class);
    }

    public function render()
    {
        unset($this->comment->likes);
        unset($this->comment->dislikes);

        return view('livewire.admin.post_comments.item');
    }
}
