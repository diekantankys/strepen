<?php

namespace App\Http\Livewire\Admin\Games;

use App\Http\Livewire\PaginationComponent;
use App\Models\Game;
use App\Models\GameAsset;
use App\Models\Setting;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class Index extends PaginationComponent
{
    use WithFileUploads;

    public bool $isCreating = false;

    public string $name = '';

    public $image;

    protected $rules = [
        'name' => 'required|string|max:255',
        'image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
    ];

    public function mount(): void
    {
        $this->isCreating = false;
        $this->name = '';
        $this->image = null;

        if (! in_array($this->sort_by, ['name', 'name_desc', 'created_at', 'created_at_desc'])) {
            $this->sort_by = null;
        }
    }

    public function createAsset(): void
    {
        $this->validate();

        $game = Game::where('slug', 'wanted')->firstOrFail();
        $imageName = Str::uuid().'.'.$this->image->extension();
        $this->image->storeAs('public/game-assets', $imageName);

        GameAsset::create([
            'game_id' => $game->id,
            'name' => $this->name,
            'image_path' => $imageName,
        ]);

        $this->name = '';
        $this->image = null;
        $this->isCreating = false;
    }

    public function render()
    {
        $game = Game::where('slug', 'wanted')->first();

        $assetsQuery = $game
            ? GameAsset::where('game_id', $game->id)
                ->when($this->query, fn ($q) => $q->where('name', 'like', '%'.$this->query.'%'))
            : GameAsset::whereNull('id');

        if ($this->sort_by === 'name') {
            $assetsQuery = $assetsQuery->orderByRaw('LOWER(name)');
        } elseif ($this->sort_by === 'name_desc') {
            $assetsQuery = $assetsQuery->orderByRaw('LOWER(name) DESC');
        } elseif ($this->sort_by === 'created_at') {
            $assetsQuery = $assetsQuery->orderBy('created_at');
        } else {
            $assetsQuery = $assetsQuery->orderByDesc('created_at');
        }

        $assets = $assetsQuery->paginate(Setting::get('pagination_rows') * 4)->withQueryString();

        return view('livewire.admin.games.index', compact('assets'))
            ->layout('layouts.app', ['title' => __('admin/games.title')]);
    }
}
