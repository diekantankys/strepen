<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostComment extends Model
{
    use SoftDeletes;

    public const MAX_REPLY_DEPTH = 3;

    protected $casts = [
        'parent_id' => 'integer',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    /** @return BelongsTo<Post, $this> */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /** @return BelongsTo<PostComment, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(PostComment::class, 'parent_id');
    }

    /** @return HasMany<PostComment, $this> */
    public function replies(): HasMany
    {
        return $this->hasMany(PostComment::class, 'parent_id')->orderBy('updated_at', 'DESC');
    }

    /** @return BelongsToMany<User, $this> */
    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_comment_likes', 'comment_id', 'user_id')->withTimestamps();
    }

    /** @return BelongsToMany<User, $this> */
    public function dislikes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_comment_dislikes', 'comment_id', 'user_id')->withTimestamps();
    }

    public function canReceiveReply(): bool
    {
        $depth = 0;
        $comment = $this;

        while ($comment->parent_id !== null) {
            $depth++;
            if ($depth >= self::MAX_REPLY_DEPTH) {
                return false;
            }
            $comment = $comment->parent()->firstOrFail();
        }

        return true;
    }

    public function deleteWithReplies(): void
    {
        $this->replies()->get()->each(
            fn (PostComment $reply) => $reply->deleteWithReplies(),
        );
        $this->delete();
    }

    public function touchThreadAndPost(): void
    {
        $comment = $this;
        while ($comment !== null) {
            $comment->touch();
            $comment = $comment->parent()->first();
        }

        $this->post()->first()?->touch();
    }

    public function like($user): void
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

    public function dislike($user): void
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
}
