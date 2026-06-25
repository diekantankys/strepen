<?php

namespace App\Http\Livewire\Admin\Games;

use App\Models\GameAsset;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Item extends Component
{
    public GameAsset $asset;

    public bool $isDeleting = false;

    public function deleteAsset(): void
    {
        Storage::delete('public/game-assets/'.$this->asset->image_path);
        $this->asset->delete();
        $this->isDeleting = false;
        $this->dispatch('refresh')->to(Index::class);
    }

    public function render()
    {
        return view('livewire.admin.games.item');
    }
}
