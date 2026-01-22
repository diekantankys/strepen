<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use SoftDeletes;

    protected $hidden = [
        'deleted_at',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $attributes = [
        'requests' => 0,
        'active' => true,
    ];

    public static function generateKey()
    {
        $key = Str::random(32);
        if (static::where('key', $key)->count() > 0) {
            return static::generateKey();
        }

        return $key;
    }

    public static function search($query, $searchQuery)
    {
        return $query->where(fn ($query) => $query->where('name', 'LIKE', '%'.$searchQuery.'%')
            ->orWhere('created_at', 'LIKE', '%'.$searchQuery.'%'));
    }
}
