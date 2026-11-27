<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\NewsPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicContentSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_slug_is_unique_and_old_title_redirects_to_the_new_title(): void
    {
        $first = NewsPost::create(['title' => 'Community News', 'body' => 'First story', 'published_at' => now()->subDay()]);
        $second = NewsPost::create(['title' => 'Community News', 'body' => 'Second story', 'published_at' => now()->subDay()]);
        $oldUrl = route('news.show', $first->public_slug);

        $this->assertNotSame($first->public_slug, $second->public_slug);
        $this->get($oldUrl)->assertOk();
        $this->get(route('news.show', $second->public_slug))->assertOk();
        $first->update(['title' => 'Updated Community News']);
        $this->get($oldUrl)->assertRedirect(route('news.show', $first->public_slug));
        $this->get('/news/'.$first->id)->assertRedirect(route('news.show', $first->public_slug));
    }

    public function test_unpublished_news_remains_unavailable_and_event_renames_redirect(): void
    {
        $draft = NewsPost::create(['title' => 'Draft', 'body' => 'Not yet public']);
        $this->get(route('news.show', $draft->public_slug))->assertNotFound();
        $this->get(route('news.show', $draft->public_id))->assertNotFound();

        $event = Event::create(['title' => 'Annual Gathering', 'event_type' => 'Other', 'start_at' => now()->addDay()]);
        $oldUrl = route('events.show', $event->public_slug);
        $event->update(['title' => 'Annual Celebration']);
        $this->get($oldUrl)->assertRedirect(route('events.show', $event->public_slug));
        $this->get(route('events.show', $event->public_slug))->assertOk();
        $this->get('/sitemap.xml')->assertSee($event->public_slug)->assertDontSee($draft->public_slug);
    }
}
