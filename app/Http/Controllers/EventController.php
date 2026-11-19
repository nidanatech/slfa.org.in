<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;

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
        $event->load('albums.media', 'programmeItems.media', 'sponsors');

        return Inertia::render('SLFA/EventDetail', [
            'event' => $event,
            'isGoing' => auth()->user()?->member ? $event->rsvps()->where('member_id', auth()->user()->member->id)->where('status', 'going')->exists() : false,
            'admissionsOpen' => $event->admissionsOpen(),
        ]);
    }

    public function admission(Request $request, Event $event)
    {
        abort_unless($event->admissionsOpen(), 403, 'Admissions are closed for this event.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9][0-9\s().-]{6,24}$/'],
            'adult_count' => ['required', 'integer', 'min:1', 'max:20'],
            'child_count' => ['required', 'integer', 'min:0', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['email'] = strtolower(trim($validated['email']));
        if ($event->admissions()->where('email', $validated['email'])->exists()) {
            throw ValidationException::withMessages(['email' => 'An admission request already exists for this email. Please contact SLFA to change it.']);
        }
        $event->admissions()->create($validated);

        return back()->with('success', 'Your admission request was received. SLFA will contact you with next steps.');
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
