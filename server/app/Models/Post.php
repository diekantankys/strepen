<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $hidden = [
        'deleted_at',
    ];

    // Generate a random image name
    public static function generateImageName($extension)
    {
        if ($extension == 'jpeg') {
            $extension = 'jpg';
        }
        $image = Str::random(32).'.'.$extension;
        if (static::where('image', $image)->count() > 0) {
            return static::generateImageName($extension);
        }

        return $image;
    }

    // A post belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    // A post belongs to many users as a like
    public function likes()
    {
        return $this->belongsToMany(User::class, 'post_likes')->withTimestamps();
    }

    // A post belongs to many users as a dislike
    public function dislikes()
    {
        return $this->belongsToMany(User::class, 'post_dislikes')->withTimestamps();
    }

    // Like a post
    public function like($user)
    {
        if ($user->id == 1) {
            return;
        }

        if ($this->likes->contains($user)) {
            $this->likes()->detach($user);
        } else {
            $this->dislikes()->detach($user);
            $this->likes()->attach($user);
        }
    }

    // Dislike a post
    public function dislike($user)
    {
        if ($user->id == 1) {
            return;
        }

        if ($this->dislikes->contains($user)) {
            $this->dislikes()->detach($user);
        } else {
            $this->likes()->detach($user);
            $this->dislikes()->attach($user);
        }
    }

    // Search by a query
    public static function search($query, $searchQuery)
    {
        return $query->where(fn ($query) => $query->where('title', 'LIKE', '%'.$searchQuery.'%')
            ->orWhere('body', 'LIKE', '%'.$searchQuery.'%')
            ->orWhere('created_at', 'LIKE', '%'.$searchQuery.'%'));
    }
}
