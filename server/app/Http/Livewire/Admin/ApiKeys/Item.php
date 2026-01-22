<?php

namespace App\Http\Livewire\Admin\ApiKeys;

use Livewire\Component;

class Item extends Component
{
    public $apiKey;

    public $isEditing = false;

    public $isDeleting = false;

    public $rules = [
        'apiKey.name' => 'required|min:2|max:48',
        'apiKey.active' => 'nullable|boolean',
    ];

    public function editApiKey()
    {
        $this->validate();
        $this->apiKey->save();
        $this->isEditing = false;
        $this->emitUp('refresh');
    }

    public function deleteApiKey()
    {
        $this->isDeleting = false;
        $this->apiKey->delete();
        $this->emitUp('refresh');
    }

    public function render()
    {
        return view('livewire.admin.api_keys.item');
    }
}
