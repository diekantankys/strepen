<?php

namespace App\Http\Livewire\Admin\ApiKeys;

use App\Http\Livewire\PaginationComponent;
use App\Models\ApiKey;
use App\Models\Setting;

class Crud extends PaginationComponent
{
    public $apiKey;

    public $isCreating;

    public $rules = [
        'apiKey.name' => 'required|min:2|max:48',
    ];

    public function mount()
    {
        if ($this->sort_by != 'name_desc' && $this->sort_by != 'created_at_desc' && $this->sort_by != 'created_at') {
            $this->sort_by = null;
        }

        $this->apiKey = new ApiKey;
        $this->isCreating = false;
    }

    public function createApiKey()
    {
        $this->validate();
        $this->apiKey->key = ApiKey::generateKey();
        $this->apiKey->save();
        $this->mount();
    }

    public function render()
    {
        $apiKeys = ApiKey::search(ApiKey::select(), $this->query);

        if ($this->sort_by == null) {
            $apiKeys = $apiKeys->orderByRaw('LOWER(name)');
        }
        if ($this->sort_by == 'name_desc') {
            $apiKeys = $apiKeys->orderByRaw('LOWER(name) DESC');
        }
        if ($this->sort_by == 'created_at_desc') {
            $apiKeys = $apiKeys->orderBy('created_at', 'DESC');
        }
        if ($this->sort_by == 'created_at') {
            $apiKeys = $apiKeys->orderBy('created_at');
        }

        return view('livewire.admin.api_keys.crud', [
            'apiKeys' => $apiKeys->paginate(Setting::get('pagination_rows') * 3)->withQueryString(),
        ])->layout('layouts.app', ['title' => __('admin/api_keys.crud.title')]);
    }
}
