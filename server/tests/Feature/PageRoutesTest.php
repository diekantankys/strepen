<?php

namespace Tests\Feature;

use App\Http\Livewire\Posts\Show;
use App\Models\Post;
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

    public function test_guest_auth_pages_render()
    {
        $this->get(route('auth.forgot_password'))->assertOk();
        $this->get(route('password.reset', ['token' => 'fake-token']))->assertOk();
    }
}
