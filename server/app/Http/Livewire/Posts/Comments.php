<?php

namespace App\Http\Livewire\Posts;

use App\Models\PostComment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Comments extends Component
{
    public $post;

    public string $body = '';

    public ?int $replyingToId = null;

    public string $replyBody = '';

    protected $rules = [
        'body' => 'required|min:1|max:1000',
    ];

    public function submitComment(): void
    {
        $this->validate();

        $comment = new PostComment;
        $comment->post_id = $this->post->id;
        $comment->user_id = Auth::id();
        $comment->body = trim($this->body);
        $comment->save();

        $this->body = '';
    }

    public function startReply(int $commentId): void
    {
        $this->replyingToId = $this->commentForCurrentPost($commentId)->id;
        $this->replyBody = '';
    }

    public function cancelReply(): void
    {
        $this->replyingToId = null;
        $this->replyBody = '';
    }

    public function submitReply(): void
    {
        $this->validate(['replyBody' => 'required|min:1|max:1000']);
        abort_if($this->replyingToId === null, 404);
        $parent = $this->commentForCurrentPost($this->replyingToId);

        $comment = new PostComment;
        $comment->post_id = $this->post->id;
        $comment->user_id = Auth::id();
        $comment->parent_id = $parent->id;
        $comment->body = trim($this->replyBody);
        $comment->save();

        $this->replyingToId = null;
        $this->replyBody = '';
    }

    public function likeComment(int $commentId): void
    {
        $comment = $this->commentForCurrentPost($commentId, ['likes', 'dislikes']);
        $comment->like(Auth::user());
    }

    public function dislikeComment(int $commentId): void
    {
        $comment = $this->commentForCurrentPost($commentId, ['likes', 'dislikes']);
        $comment->dislike(Auth::user());
    }

    public function deleteComment(int $commentId): void
    {
        $comment = $this->commentForCurrentPost($commentId);
        if (Auth::id() !== $comment->user_id && !Auth::user()->manager) {
            abort(403);
        }
        $comment->delete();
    }

    public function render()
    {
        $this->post->load([
            'comments.user',
            'comments.likes',
            'comments.dislikes',
            'comments.replies.user',
            'comments.replies.likes',
            'comments.replies.dislikes',
            'comments.replies.replies.user',
            'comments.replies.replies.likes',
            'comments.replies.replies.dislikes',
        ]);

        return view('livewire.posts.comments');
    }

    private function commentForCurrentPost(int $commentId, array $with = []): PostComment
    {
        return PostComment::query()
            ->where('post_id', $this->post->id)
            ->with($with)
            ->findOrFail($commentId);
    }
}
