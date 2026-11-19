<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HappeningsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_and_events_share_one_public_page(): void
    {
        $this->get('/happenings')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('SLFA/Happenings')
            ->has('posts')
            ->has('upcomingEvents')
            ->has('pastEvents'));

        $this->get('/news')->assertRedirect('/happenings#news');
        $this->get('/events')->assertRedirect('/happenings#events');
    }
}
