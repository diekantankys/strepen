<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PostCommentResource;
use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApiPostCommentsController extends ApiController
{
    // Api post comments index route
    public function index(Post $post)
    {
        $comments = $post->comments()
            ->with([
                'user', 'likes', 'dislikes',
                'replies.user', 'replies.likes', 'replies.dislikes',
                'replies.replies.user', 'replies.replies.likes', 'replies.replies.dislikes',
                'replies.replies.replies.user', 'replies.replies.replies.likes', 'replies.replies.replies.dislikes',
            ])
            ->get();

        return PostCommentResource::collection($comments);
    }

    // Api post comments store route
    public function store(Request $request, Post $post)
    {
        $request->validate([
            'body' => 'required|min:1|max:1000',
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('post_comments', 'id')->where(
                    fn ($query) => $query->where('post_id', $post->id),
                ),
            ],
        ]);

        $comment = new PostComment;
        $comment->post_id = $post->id;
        $comment->user_id = $request->user()->id;
        $comment->parent_id = $request->input('parent_id');
        $comment->body = $request->input('body');
        $comment->save();
        $comment->load('user', 'likes', 'dislikes');

        return new PostCommentResource($comment);
    }

    // Api post comments like route
    public function like(Request $request, Post $post, PostComment $comment)
    {
        $this->ensureCommentBelongsToPost($post, $comment);
        $comment->like($request->user());
        $comment->load('likes', 'dislikes');

        return new PostCommentResource($comment);
    }

    // Api post comments dislike route
    public function dislike(Request $request, Post $post, PostComment $comment)
    {
        $this->ensureCommentBelongsToPost($post, $comment);
        $comment->dislike($request->user());
        $comment->load('likes', 'dislikes');

        return new PostCommentResource($comment);
    }

    // Api post comments destroy route
    public function destroy(Request $request, Post $post, PostComment $comment)
    {
        $this->ensureCommentBelongsToPost($post, $comment);
        if ($request->user()->id !== $comment->user_id && !$request->user()->manager) {
            abort(403);
        }
        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }

    private function ensureCommentBelongsToPost(Post $post, PostComment $comment): void
    {
        abort_unless($comment->post_id === $post->id, 404);
    }
}
