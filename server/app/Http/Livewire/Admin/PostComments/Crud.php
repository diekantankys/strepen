<?php

namespace App\Http\Livewire\Admin\PostComments;

use App\Http\Livewire\PaginationComponent;
use App\Models\PostComment;
use App\Models\Setting;

class Crud extends PaginationComponent
{
    public $queryString = [
        'sort_by' => ['except' => ''],
        'query' => ['except' => ''],
    ];

    public $listeners = ['refresh' => '$refresh'];

    public function mount()
    {
        if ($this->sort_by != 'created_at' && $this->sort_by != 'created_at_desc') {
            $this->sort_by = null;
        }
    }

    public function render()
    {
        $comments = PostComment::select()
            ->with(['post', 'user', 'likes', 'dislikes'])
            ->when($this->query, fn ($q) => $q->where('body', 'LIKE', '%'.$this->query.'%'))
            ->when($this->sort_by == 'created_at', fn ($q) => $q->orderBy('created_at'))
            ->when($this->sort_by == 'created_at_desc' || !$this->sort_by, fn ($q) => $q->orderBy('created_at', 'DESC'));

        return view('livewire.admin.post_comments.crud', [
            'comments' => $comments->paginate(Setting::get('pagination_rows') * 3)->withQueryString(),
        ])->layout('layouts.app', ['title' => __('admin/post_comments.crud.title')]);
    }
}
