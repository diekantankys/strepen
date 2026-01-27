<?php

namespace App\Http\Resources;

use App\Helpers\BetterParsedown;
use App\Models\Post;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Post
 */
class PostResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'image' => $this->image != null ? asset('/storage/posts/'.$this->image) : null,
            'body' => BetterParsedown::instance()->text($this->body),
            'created_at' => $this->created_at,
            'updated_at' => $this->when($request->user()->manager, $this->updated_at),
            'likes' => $this->likes->count(),
            'user_liked' => $this->likes->contains($request->user()),
            'dislikes' => $this->dislikes->count(),
            'user_disliked' => $this->dislikes->contains($request->user()),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
