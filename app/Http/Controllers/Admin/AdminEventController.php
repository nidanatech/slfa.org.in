<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminEventController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Events/Index', [
            'events' => Event::withCount('rsvps')->latest('start_at')->paginate(20),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Events/Create');
    }

    public function store(Request $request)
    {
        $this->saveEvent($request);

        return redirect()->route('admin.events.index')->with('success', 'Event created.');
    }

    public function edit(Event $event)
    {
        return Inertia::render('Admin/Events/Create', ['event' => $event->load('programmeItems', 'sponsors')]);
    }

    public function update(Request $request, Event $event)
    {
        $this->saveEvent($request, $event);

        return redirect()->route('admin.events.index')->with('success', 'Event updated.');
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return redirect()->route('admin.events.index')->with('success', 'Event deleted.');
    }

    public function attendees(Event $event)
    {
        $event->load(['rsvps.member.user']);
        return Inertia::render('Admin/Events/Attendees', [
            'event' => $event,
        ]);
    }

    private function saveEvent(Request $request, ?Event $event = null): Event
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|in:AGM,Get-Together,Medical Camp,Eye Camp,Health Checkup,Other',
            'location' => 'nullable|string|max:255',
            'venue_name' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string|max:2000',
            'timezone' => 'required|timezone',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'dress_code' => 'nullable|string|max:255',
            'adult_price' => 'nullable|integer|min:0',
            'child_price' => 'nullable|integer|min:0',
            'child_age_range' => 'nullable|string|max:255',
            'contact_details' => 'nullable|string|max:255',
            'rsvp_enabled' => 'boolean',
            'cover_image' => 'nullable|image|mimes:jpeg,png,webp,avif|max:5120',
            'programme' => 'nullable|array|max:50',
            'programme.*.time' => ['required', 'regex:/^([01]\\d|2[0-3]):[0-5]\\d$/'],
            'programme.*.title' => 'required|string|max:255',
            'programme.*.details' => 'nullable|string|max:2000',
            'sponsors' => 'nullable|array|max:20',
            'sponsors.*.name' => 'required|string|max:255',
            'sponsors.*.website_url' => 'nullable|url|starts_with:https://|max:2048',
            'sponsors.*.tier' => 'nullable|string|max:255',
        ]);

        $programme = $validated['programme'] ?? [];
        $sponsors = $validated['sponsors'] ?? [];
        unset($validated['programme'], $validated['sponsors'], $validated['cover_image']);

        $zone = $validated['timezone'];
        $validated['start_at'] = Carbon::parse($validated['start_at'], $zone)->utc();
        $validated['end_at'] = ($validated['end_at'] ?? null) ? Carbon::parse($validated['end_at'], $zone)->utc() : null;

        if ($request->hasFile('cover_image')) {
            $validated['cover_image_path'] = $request->file('cover_image')->store('event-covers', 'public');
            if ($event?->cover_image_path && ! str_starts_with($event->cover_image_path, '/')) {
                Storage::disk('public')->delete($event->cover_image_path);
            }
        }

        return DB::transaction(function () use ($event, $validated, $programme, $sponsors) {
            if ($event) {
                $event->update($validated);
                $event->programmeItems()->delete();
                $event->sponsors()->delete();
            } else {
                $event = Event::create($validated);
            }

            foreach ($programme as $position => $item) {
                $event->programmeItems()->create([
                    'time' => $item['time'],
                    'title' => $item['title'],
                    'details' => $item['details'] ?? null,
                    'sort_order' => $position,
                ]);
            }
            foreach ($sponsors as $position => $item) {
                $event->sponsors()->create([
                    'name' => $item['name'],
                    'website_url' => $item['website_url'] ?? null,
                    'tier' => $item['tier'] ?? null,
                    'sort_order' => $position,
                ]);
            }

            return $event;
        });
    }
}
