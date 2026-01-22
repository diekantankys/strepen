<?php

namespace App\Http\Livewire;

use App\Models\Post;
use App\Models\Setting;
use App\Models\User;

class Home extends PaginationComponent
{
    public $user_id;

    public $userIdTemp;

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
            $user = User::where('id', $this->user_id)->where('active', true)->withCount('posts')->first();
            if ($user == null || $user->posts_count == 0) {
                $this->user_id = null;
            }
        }
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

        return view('livewire.home', [
            'posts' => $posts->with('user')->paginate(Setting::get('pagination_rows'))->withQueryString(),
        ])->layout('layouts.app', ['title' => __('home.title')]);
    }
}
