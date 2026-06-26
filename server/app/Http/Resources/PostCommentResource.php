<?php

namespace App\Http\Resources;

use App\Models\PostComment;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PostComment
 */
class PostCommentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'body' => $this->body,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'likes' => $this->likes->count(),
            'user_liked' => $this->likes->contains($request->user()),
            'dislikes' => $this->dislikes->count(),
            'user_disliked' => $this->dislikes->contains($request->user()),
            'user' => new UserResource($this->whenLoaded('user')),
            'replies' => PostCommentResource::collection($this->whenLoaded('replies')),
        ];
    }
}
