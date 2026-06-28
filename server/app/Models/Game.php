<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends Model
{
    use SoftDeletes;

    protected $hidden = ['deleted_at'];

    protected $fillable = ['name', 'slug'];

    public function scores(): HasMany
    {
        return $this->hasMany(GameScore::class);
    }
}
