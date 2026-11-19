<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use App\Models\NewsPost;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HappeningsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_and_events_share_one_public_page(): void
    {
        $this->assertSame(9, NewsPost::published()->count());
        $this->assertSame('Fourth Annual General Meeting', NewsPost::published()->first()->title);
        $this->assertSame('Medical and Eye Camp in Devaneri', NewsPost::published()->get()->last()->title);

        $this->get('/happenings')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('SLFA/Happenings')
            ->has('posts')
            ->has('upcomingEvents')
            ->has('pastEvents'));

        $this->get('/news')->assertRedirect('/happenings#news');
        $this->get('/events')->assertRedirect('/happenings#events');
    }

    public function test_admin_can_replace_an_imported_news_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $post = NewsPost::where('title', 'Fourth Annual General Meeting')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.news.update', $post), [
            '_method' => 'put',
            'title' => $post->title,
            'category' => $post->category,
            'body' => $post->body,
            'published_at' => '2023-09-30',
            'cover_image' => UploadedFile::fake()->image('agm.jpg'),
        ])->assertRedirect();

        $post->refresh();
        $this->assertStringStartsWith('news-covers/', $post->cover_image_path);
        Storage::disk('public')->assertExists($post->cover_image_path);
    }
}
