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
            'isGoing' => auth()->user()?->member ? $event->rsvps()->where('member_id', auth()->user()->member->id)->where('status', 'going')->exists() : false,
        ]);
    }

    public function rsvp(Request $request, Event $event)
    {
        $request->validate([
            'status' => 'required|in:going,maybe,declined',
        ]);

        $user = $request->user();
        
        abort_unless($user?->account_status === 'active' && $user->member && $event->rsvp_enabled && $event->start_at->isFuture(), 403, 'RSVP is unavailable for this account or event.');

        $event->rsvps()->updateOrCreate(
            ['member_id' => $user->member->id],
            ['status' => $request->status]
        );

        return back()->with('success', 'Your RSVP has been updated.');
    }
}
