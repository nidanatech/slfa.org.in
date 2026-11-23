<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventAdmission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminEventController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Events/Index', [
            'events' => Event::withCount(['rsvps', 'admissions'])->latest('start_at')->paginate(20),
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
        $event->load(['rsvps.member.user', 'admissions']);
        return Inertia::render('Admin/Events/Attendees', [
            'event' => $event,
        ]);
    }

    public function updateAdmission(Request $request, Event $event, EventAdmission $admission)
    {
        abort_unless($admission->event_id === $event->id, 404);
        $validated = $request->validate(['status' => ['required', Rule::in(['requested', 'confirmed', 'declined', 'cancelled'])]]);
        $admission->update($validated);

        return back()->with('success', 'Admission status updated.');
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
            'admission_enabled' => 'boolean',
            'admission_closes_at' => 'nullable|date|before_or_equal:start_at',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:320',
            'cover_image' => 'nullable|image|mimes:jpeg,png,webp,avif|max:5120',
            'programme_image' => 'nullable|image|mimes:jpeg,png,webp,avif|max:5120',
            'og_image' => 'nullable|image|mimes:jpeg,png,webp,avif|max:5120',
            'programme' => 'nullable|array|max:50',
            'programme.*.id' => [$event ? 'nullable' : 'prohibited', 'integer', Rule::exists('event_programme_items', 'id')->where('event_id', $event?->id)],
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
        unset($validated['programme'], $validated['sponsors'], $validated['cover_image'], $validated['programme_image'], $validated['og_image']);

        $zone = $validated['timezone'];
        $validated['start_at'] = Carbon::parse($validated['start_at'], $zone)->utc();
        $validated['end_at'] = ($validated['end_at'] ?? null) ? Carbon::parse($validated['end_at'], $zone)->utc() : null;
        $validated['admission_closes_at'] = ($validated['admission_closes_at'] ?? null) ? Carbon::parse($validated['admission_closes_at'], $zone)->utc() : null;

        if ($request->hasFile('cover_image')) {
            $validated['cover_image_path'] = $request->file('cover_image')->store('event-covers', 'public');
            if ($event?->cover_image_path && ! str_starts_with($event->cover_image_path, '/')) {
                Storage::disk('public')->delete($event->cover_image_path);
            }
        }
        if ($request->hasFile('programme_image')) {
            $validated['programme_image_path'] = $request->file('programme_image')->store('event-programmes', 'public');
            if ($event?->programme_image_path && ! str_starts_with($event->programme_image_path, '/')) {
                Storage::disk('public')->delete($event->programme_image_path);
            }
        }
        if ($request->hasFile('og_image')) {
            $validated['og_image_path'] = $request->file('og_image')->store('event-social', 'public');
        }

        return DB::transaction(function () use ($event, $validated, $programme, $sponsors) {
            if ($event) {
                $event->update($validated);
                $keepIds = collect($programme)->pluck('id')->filter()->all();
                $event->programmeItems()->whereNotIn('id', $keepIds)->delete();
                $event->sponsors()->delete();
            } else {
                $event = Event::create($validated);
            }

            foreach ($programme as $position => $item) {
                $details = [
                    'time' => $item['time'],
                    'title' => $item['title'],
                    'details' => $item['details'] ?? null,
                    'sort_order' => $position,
                ];
                if (! empty($item['id'])) {
                    $event->programmeItems()->whereKey($item['id'])->update($details);
                } else {
                    $event->programmeItems()->create($details);
                }
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
