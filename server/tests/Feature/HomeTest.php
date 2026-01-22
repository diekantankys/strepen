<?php

namespace Tests\Feature;

use App\Http\LiveWire\Home;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire;
use Tests\TestCase;

class HomeTest extends TestCase
{
    // Test home page
    public function test_home()
    {
        $this->get('/')->assertStatus(200);
    }

    // Test home page to see posts when guest
    public function test_see_posts_guest()
    {
        User::factory()
            ->has(Post::factory()->count(5))
            ->create();

        Livewire::test(Home::class)
            ->assertDontSee(Post::orderBy('created_at')->first()->title);
    }

    // Test home page to see posts when authed
    public function test_see_posts_authed()
    {
        $user = User::factory()
            ->has(Post::factory()->count(5))
            ->create();
        $this->actingAs($user);

        Livewire::test(Home::class)
            ->assertSee(Post::orderBy('created_at')->first()->title);
    }

    // Test home page to search posts
    public function test_search_posts()
    {
        $user = User::factory()
            ->has(Post::factory()->count(5))
            ->create();
        $this->actingAs($user);

        $searchPost = Post::all()->random();
        Livewire::test(Home::class)
            ->set('query', Str::substr($searchPost->title, 0, 8))
            ->call('search')
            ->assertSee($searchPost->title);
    }
}
