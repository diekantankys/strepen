<?php

namespace App\Http\Livewire\Admin\Posts;

use App\Http\Livewire\PaginationComponent;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\NewPost;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

class Crud extends PaginationComponent
{
    use WithFileUploads;

    public $user_id;

    public $userIdTemp;

    public $post;

    public $sendNotification;

    public $image;

    public $isCreating;

    public $rules = [
        'post.title' => 'required|min:2|max:128',
        'image' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
        'post.body' => 'required|min:2',
        'sendNotification' => 'required|boolean',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->queryString[] = 'user_id';
        $this->listeners[] = 'inputValue';
    }

    public function mount()
    {
        if ($this->sort_by != 'created_at' && $this->sort_by != 'title' && $this->sort_by != 'title_desc') {
            $this->sort_by = null;
        }

        if ($this->user_id != 1) {
            $user = User::where('id', $this->user_id)->withCount('posts')->first();
            if ($user == null || $user->posts_count == 0) {
                $this->user_id = null;
            }
        }

        $this->post = new Post;
        $this->sendNotification = true;
        $this->image = null;
        $this->isCreating = false;
    }

    public function inputValue($name, $value)
    {
        if ($name == 'user_filter') {
            $this->userIdTemp = $value;
        }
    }

    public function search()
    {
        $this->user_id = $this->userIdTemp;
        $this->resetPage();
    }

    public function createPost()
    {
        $this->validate();

        if ($this->image != null) {
            $imageName = Post::generateImageName($this->image->extension());
            $this->image->storeAs('public/posts', $imageName);
            $this->post->image = $imageName;
        }

        $this->post->user_id = Auth::id();
        $this->post->save();

        if ($this->sendNotification) {
            // Send all users the receive news new post notification
            $users = User::where('active', true)->where('receive_news', true)->get();
            foreach ($users as $user) {
                $user->notify(new NewPost($user, $this->post));
            }
        }

        $this->mount();
    }

    public function render()
    {
        $posts = Post::search(Post::select(), $this->query);
        if ($this->user_id != null) {
            $posts = $posts->where('user_id', $this->user_id);
        }

        if ($this->sort_by == null) {
            $posts = $posts->orderBy('created_at', 'DESC');
        }
        if ($this->sort_by == 'created_at') {
            $posts = $posts->orderBy('created_at');
        }
        if ($this->sort_by == 'title') {
            $posts = $posts->orderByRaw('LOWER(title)');
        }
        if ($this->sort_by == 'title_desc') {
            $posts = $posts->orderByRaw('LOWER(title) DESC');
        }

        return view('livewire.admin.posts.crud', [
            'posts' => $posts->with('user')
                ->paginate(Setting::get('pagination_rows') * 3)->withQueryString(),
        ])->layout('layouts.app', ['title' => __('admin/posts.crud.title')]);
    }
}
