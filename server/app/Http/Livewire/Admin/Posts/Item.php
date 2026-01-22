<?php

namespace App\Http\Livewire\Admin\Posts;

use App\Models\Post;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Item extends Component
{
    use WithFileUploads;

    public $post;

    public $image;

    public $createdAtDate;

    public $createdAtTime;

    public $isShowing = false;

    public $isEditing = false;

    public $isDeleting = false;

    public $rules = [
        'post.user_id' => 'required|integer|exists:users,id',
        'post.title' => 'required|min:2|max:128',
        'image' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
        'createdAtDate' => 'required|date_format:Y-m-d',
        'createdAtTime' => 'required|date_format:H:i:s',
        'post.body' => 'required|min:2',
    ];

    public $listeners = ['inputValue'];

    public function mount()
    {
        $this->createdAtDate = $this->post->created_at->format('Y-m-d');
        $this->createdAtTime = $this->post->created_at->format('H:i:s');
    }

    public function inputValue($name, $value)
    {
        if ($name == 'item_user') {
            $this->post->user_id = $value;
        }
    }

    public function editPost()
    {
        $this->emit('inputValidate', 'item_user');
        $this->validate();

        if ($this->image != null) {
            $imageName = Post::generateImageName($this->image->extension());
            $this->image->storeAs('public/posts', $imageName);

            if ($this->post->image != null) {
                Storage::delete('public/posts/'.$this->post->image);
            }
            $this->post->image = $imageName;
            $this->image = null;
        }

        $this->post->created_at = $this->createdAtDate.' '.$this->createdAtTime;
        $this->post->save();

        $this->isEditing = false;
        $this->emitUp('refresh');
    }

    public function deleteImage()
    {
        if ($this->post->image != null) {
            Storage::delete('public/posts/'.$this->post->image);
        }
        $this->post->image = null;
        $this->post->save();
        $this->emitUp('refresh');
    }

    public function deletePost()
    {
        $this->isDeleting = false;
        $this->post->delete();
        $this->emitUp('refresh');
    }

    public function render()
    {
        unset($this->post->user);

        return view('livewire.admin.posts.item');
    }
}
