<?php

namespace App\Http\Resources;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request)
    {
        $type = 'unknown';
        if ($this->type == 'App\Notifications\NewDeposit') {
            $type = 'new_deposit';
        }
        if ($this->type == 'App\Notifications\NewPost') {
            $type = 'new_post';
        }
        if ($this->type == 'App\Notifications\LowBalance') {
            $type = 'low_balance';
        }

        $data = [
            'id' => $this->id,
            'type' => $type,
            'data' => $this->data,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->when($request->user()->manager, $this->updated_at),
        ];
        if ($this->notifiable_type == 'App\Models\User') {
            $data['user'] = new UserResource(User::find($this->notifiable_id));
        }
        if ($this->type == 'App\Notifications\NewPost') {
            $data['post'] = new PostResource(Post::withTrashed()->find($this->data['post_id']));
        }

        return $data;
    }
}
