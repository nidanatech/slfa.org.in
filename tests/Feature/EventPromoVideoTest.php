<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventPromoVideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_replace_and_remove_an_event_video(): void
    {
        $this->travelTo(now()->setDate(2027, 1, 1));
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::create(['title' => 'Upcoming gathering', 'event_type' => 'Other', 'start_at' => now()->addDays(2)]);
        $details = ['title' => $event->title, 'event_type' => 'Other', 'timezone' => 'Asia/Kolkata', 'start_at' => now()->addDays(2)->format('Y-m-d\TH:i')];

        $this->actingAs($admin)->put(route('admin.events.update', $event), $details + [
            'promo_video' => UploadedFile::fake()->create('advert.mp4', 100, 'video/mp4'),
            'promo_poster' => UploadedFile::fake()->image('poster.jpg'),
            'promo_transcript' => 'Join us for the gathering.',
        ])->assertRedirect();
        $event->refresh();
        $oldVideo = $event->promo_video_path;
        $poster = $event->promo_poster_path;
        Storage::disk('public')->assertExists($oldVideo);
        Storage::disk('public')->assertExists($poster);
        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('upcomingEvents.0.promo_video_path', $oldVideo));
        $this->get(route('events.show', $event->public_slug))->assertInertia(fn (Assert $page) => $page->where('event.promo_transcript', 'Join us for the gathering.'));

        $this->put(route('admin.events.update', $event), $details + [
            'promo_video' => UploadedFile::fake()->create('new.mp4', 100, 'video/mp4'),
            'promo_transcript' => 'Updated transcript.',
        ])->assertRedirect();
        $event->refresh();
        Storage::disk('public')->assertMissing($oldVideo);
        Storage::disk('public')->assertExists($event->promo_video_path);
        $this->assertSame($poster, $event->promo_poster_path);
        $newVideo = $event->promo_video_path;

        $this->put(route('admin.events.update', $event), $details + ['remove_promo_video' => true, 'remove_promo_poster' => true])->assertRedirect();
        $event->refresh();
        $this->assertNull($event->promo_video_path);
        $this->assertNull($event->promo_poster_path);
        Storage::disk('public')->assertMissing($newVideo);
        Storage::disk('public')->assertMissing($poster);
    }

    public function test_invalid_video_is_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::create(['title' => 'Gathering', 'event_type' => 'Other', 'start_at' => now()->addDays(2)]);
        $this->actingAs($admin)->put(route('admin.events.update', $event), [
            'title' => $event->title, 'event_type' => 'Other', 'timezone' => 'Asia/Kolkata',
            'start_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'promo_video' => UploadedFile::fake()->create('invalid.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('promo_video');
        $this->assertNull($event->fresh()->promo_video_path);
    }
}
