<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\NewsPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GalaEventAndNewsGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_gala_is_upcoming_with_programme_sponsors_and_venue(): void
    {
        $gala = Event::where('title', '7th Annual Gala Cocktail Dinner 2026')->firstOrFail();

        $this->assertSame('2026-11-28 13:30:00', $gala->start_at->format('Y-m-d H:i:s'));
        $this->assertSame('The Leela Palace, Royal Ballroom', $gala->venue_name);
        $this->assertSame(11, $gala->programmeItems()->count());
        $this->assertSame(5, $gala->sponsors()->count());
        $this->assertFalse($gala->rsvp_enabled);

        $this->get(route('events.show', $gala->public_id))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('SLFA/EventDetail')
            ->has('event.programme_items', 11)
            ->has('event.sponsors', 5));

        $this->get('/gallery')->assertRedirect('/happenings#news');
    }

    public function test_admin_can_edit_event_programme_sponsor_url_and_local_time(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $gala = Event::where('title', '7th Annual Gala Cocktail Dinner 2026')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.events.update', $gala), [
            '_method' => 'put',
            'title' => $gala->title,
            'event_type' => 'Other',
            'timezone' => 'Asia/Kolkata',
            'start_at' => '2026-11-28T19:00',
            'venue_name' => $gala->venue_name,
            'rsvp_enabled' => false,
            'programme' => [['time' => '18:30', 'title' => 'Registration', 'details' => 'Royal Ballroom']],
            'sponsors' => [['name' => 'Example Partner', 'website_url' => 'https://example.org', 'tier' => 'Partner']],
        ])->assertRedirect();

        $gala->refresh();
        $this->assertSame('2026-11-28 13:30:00', $gala->start_at->format('Y-m-d H:i:s'));
        $this->assertSame('Registration', $gala->programmeItems()->first()->title);
        $this->assertSame('https://example.org', $gala->sponsors()->first()->website_url);
    }

    public function test_admin_can_add_and_remove_photos_from_a_news_story(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $news = NewsPost::where('title', 'Fourth Annual General Meeting')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.news.update', $news), [
            '_method' => 'put',
            'title' => $news->title,
            'body' => $news->body,
            'category' => $news->category,
            'published_at' => '2023-09-30',
            'gallery_images' => [UploadedFile::fake()->image('one.jpg'), UploadedFile::fake()->image('two.jpg')],
        ])->assertRedirect();

        $this->assertSame(2, $news->photos()->count());
        $this->get(route('news.show', $news->public_id))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('SLFA/NewsDetail')
            ->has('post.photos', 2));

        $photo = $news->photos()->firstOrFail();
        Storage::disk('public')->assertExists($photo->path);
        $this->actingAs($admin)->delete(route('admin.news.photos.destroy', [$news, $photo]))->assertRedirect();
        Storage::disk('public')->assertMissing($photo->path);
        $this->assertSame(1, $news->photos()->count());
    }
}
