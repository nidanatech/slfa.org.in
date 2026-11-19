<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventPublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_event_admission_respects_cutoff_and_prevents_duplicate_requests(): void
    {
        $event = Event::create([
            'title' => 'Community dinner', 'event_type' => 'Other',
            'start_at' => now()->addDays(10), 'admission_enabled' => true,
            'admission_closes_at' => now()->addDays(5),
            'meta_title' => 'Community dinner in Chennai', 'meta_description' => 'Join the Sri Lankan community for dinner.',
            'og_image_path' => '/images/Sri-Lanka-Culture.webp',
        ]);
        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $event->public_id);
        $this->get('/events/'.$event->id)->assertRedirect(route('events.show', $event->public_id));
        $this->get(route('events.show', $event->public_id))->assertOk()
            ->assertSee('<meta name="description" content="Join the Sri Lankan community for dinner.">', false)
            ->assertSee('Community dinner in Chennai | SLFA')
            ->assertSee('OfflineEventAttendanceMode')
            ->assertSee('/images/Sri-Lanka-Culture.webp')
            ->assertInertia(fn (Assert $page) => $page->where('admissionsOpen', true));

        $request = ['name' => 'Example Guest', 'email' => 'guest@example.com', 'phone' => '+91 9876543210', 'adult_count' => 2, 'child_count' => 0];
        $this->from(route('events.show', $event->public_id))->post(route('events.admission', $event->public_id), $request)->assertRedirect();
        $this->assertDatabaseHas('event_admissions', ['event_id' => $event->id, 'email' => 'guest@example.com', 'status' => 'requested']);
        $admission = $event->admissions()->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->patch(route('admin.events.admissions.update', [$event, $admission]), ['status' => 'confirmed'])->assertRedirect();
        $this->assertDatabaseHas('event_admissions', ['id' => $admission->id, 'status' => 'confirmed']);
        $this->post(route('logout'));
        $this->from(route('events.show', $event->public_id))->post(route('events.admission', $event->public_id), $request)->assertSessionHasErrors('email');

        $event->update(['admission_closes_at' => now()->subMinute()]);
        $this->post(route('events.admission', $event->public_id), array_replace($request, ['email' => 'other@example.com']))->assertForbidden();
    }

    public function test_programme_photos_remain_linked_after_admin_edits_the_schedule(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::create(['title' => 'Past gathering', 'event_type' => 'Other', 'start_at' => now()->subDay()]);
        $item = $event->programmeItems()->create(['time' => '18:30', 'title' => 'Guest arrival and registration', 'sort_order' => 0]);
        $album = GalleryAlbum::create(['event_id' => $event->id, 'title' => 'Gathering photos']);

        $this->actingAs($admin)->post(route('admin.albums.upload', $album), [
            'file' => UploadedFile::fake()->image('arrival.jpg'), 'programme_item_id' => $item->id, 'caption' => 'Guests arrive',
        ])->assertRedirect();
        $media = $album->media()->firstOrFail();

        $this->actingAs($admin)->put(route('admin.events.update', $event), [
            'title' => 'Past gathering', 'event_type' => 'Other', 'timezone' => 'Asia/Kolkata',
            'start_at' => now()->subDay()->setTimezone('Asia/Kolkata')->format('Y-m-d\TH:i'),
            'programme' => [['id' => $item->id, 'time' => '18:45', 'title' => 'Guest arrival and registration']],
        ])->assertRedirect();

        $this->assertDatabaseHas('gallery_media', ['id' => $media->id, 'programme_item_id' => $item->id]);
        $this->get(route('events.show', $event->public_id))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('event.programme_items.0.media', 1)
            ->where('event.programme_items.0.title', 'Guest arrival and registration'));
    }
}
