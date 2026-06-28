<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameAsset extends Model
{
    use SoftDeletes;

    protected $hidden = ['deleted_at'];

    protected $fillable = ['game_id', 'name', 'image_path'];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
