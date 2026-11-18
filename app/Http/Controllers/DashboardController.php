<?php

namespace App\Http\Controllers;

use App\Models\NewsPost;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the member dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user()->load('profile');

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        // Fetch recent news (published only)
        $recentNews = NewsPost::published()
            ->latest()
            ->take(5)
            ->get();

        // Fetch upcoming events
        $upcomingEvents = Event::upcoming()
            ->take(3)
            ->get();

        // Feed data (combining news and events, sorted by date)
        $feed = collect();
        
        foreach ($recentNews as $news) {
            $feed->push([
                'type' => 'news',
                'id' => $news->id,
                'title' => $news->title,
                'excerpt' => $news->excerpt,
                'content' => $news->content,
                'author' => 'SLFA Executive Committee',
                'date' => $news->published_at,
                'image_url' => $news->featured_image ? asset('storage/' . $news->featured_image) : null,
            ]);
        }

        foreach ($upcomingEvents as $event) {
            $feed->push([
                'type' => 'event',
                'id' => $event->id,
                'title' => $event->title,
                'excerpt' => $event->description,
                'author' => 'SLFA Events Team',
                'date' => $event->start_time,
                'location' => $event->location,
                'image_url' => $event->featured_image ? asset('storage/' . $event->featured_image) : null,
            ]);
        }

        // Sort feed by date descending
        $feed = $feed->sortByDesc('date')->values();

        return Inertia::render('Dashboard/Index', [
            'user' => $user,
            'feed' => $feed,
            'upcomingEvents' => $upcomingEvents, // separate for the right sidebar
        ]);
    }
}
