<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameAsset extends Model
{
    protected $fillable = ['game_id', 'name', 'image_path'];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
