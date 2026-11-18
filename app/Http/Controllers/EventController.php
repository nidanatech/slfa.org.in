<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventController extends Controller
{
    public function index()
    {
        return Inertia::render('SLFA/Events', [
            'upcomingEvents' => Event::upcoming()->get(),
            'pastEvents' => Event::past()->get(),
        ]);
    }

    public function show(Event $event)
    {
        // Load the gallery albums for this event if any
        $event->load('albums.media');

        return Inertia::render('SLFA/EventDetail', [
            'event' => $event,
            'isGoing' => auth()->check() ? $event->rsvps()->where('user_id', auth()->id())->where('status', 'going')->exists() : false,
        ]);
    }

    public function rsvp(Request $request, Event $event)
    {
        $request->validate([
            'status' => 'required|in:going,maybe,declined',
        ]);

        $user = $request->user();
        
        abort_unless($user && $user->account_status === 'active', 403, 'Only active members can RSVP.');

        $event->rsvps()->updateOrCreate(
            ['user_id' => $user->id],
            ['status' => $request->status]
        );

        return back()->with('success', 'Your RSVP has been updated.');
    }
}
