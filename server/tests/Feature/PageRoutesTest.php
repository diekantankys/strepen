<?php

namespace Tests\Feature;

use App\Http\Livewire\Posts\Show;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use Livewire;
use Tests\TestCase;

class PageRoutesTest extends TestCase
{
    public function test_static_pages_render()
    {
        $this->get(route('apps'))->assertOk();
        $this->get(route('release-notes'))->assertOk();
        $this->get(route('account-deletion'))->assertOk();
    }

    public function test_post_show_requires_authentication_and_renders_for_users()
    {
        $post = Post::factory()->for(User::factory())->create();

        $this->get(route('posts.show', $post))->assertRedirect(route('auth.login'));

        $this->actingAs(User::factory()->create());

        $this->get(route('posts.show', $post))->assertOk();

        Livewire::test(Show::class, ['post' => $post])
            ->assertSee($post->title);
    }

    public function test_post_comments_render_reactions_with_post_item_responsive_layout()
    {
        $post = Post::factory()->for(User::factory())->create();
        $commentUser = User::factory()->create([
            'firstname' => 'Comment',
            'insertion' => null,
            'lastname' => 'Author',
        ]);
        $comment = new PostComment;
        $comment->post_id = $post->id;
        $comment->user_id = $commentUser->id;
        $comment->body = 'Comment body';
        $comment->save();

        $this->actingAs(User::factory()->create());

        Livewire::test(Show::class, ['post' => $post])
            ->assertSeeInOrder([
                '<strong>Comment Author</strong>',
                'buttons is-pulled-right is-hidden-touch',
                'wire:click="likeComment('.$comment->id.')"',
                'wire:click="dislikeComment('.$comment->id.')"',
                'Comment body',
                'buttons is-display-touch is-hidden-desktop is-justify-content-flex-end',
                'wire:click="likeComment('.$comment->id.')"',
                'wire:click="dislikeComment('.$comment->id.')"',
            ], false);
    }

    public function test_guest_auth_pages_render()
    {
        $this->get(route('auth.forgot_password'))->assertOk();
        $this->get(route('password.reset', ['token' => 'fake-token']))->assertOk();
    }
}
