<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;

abstract class PaginationComponent extends Component
{
    use WithPagination;

    public $sort_by;

    public $query;

    public $queryString = [
        'sort_by' => ['except' => ''],
        'query' => ['except' => ''],
    ];

    public $listeners = ['refresh' => '$refresh'];

    public function updatedSortBy()
    {
        $this->resetPage();
    }

    public function search()
    {
        $this->resetPage();
    }

    public function paginationView()
    {
        return 'layouts.pagination';
    }

    public function _previousPage($disabled)
    {
        if (! $disabled) {
            $this->previousPage();
        }
    }

    public function _nextPage($disabled)
    {
        if (! $disabled) {
            $this->nextPage();
        }
    }
}
