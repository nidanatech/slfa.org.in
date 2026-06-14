<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'calendar_id' => 'required|exists:calendars,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'location' => 'nullable|string|max:255',
            'mode' => 'nullable|in:in_person,phone,video,chat',
            'contact_id' => 'nullable|exists:contacts,id',
            'product_id' => 'nullable|exists:products,id',
            'product_variation_id' => 'nullable|exists:product_variations,id',
            'opportunity_id' => 'nullable|exists:opportunities,id',
            'status' => 'nullable|string',
        ]);

        $event = Event::create([
            'calendar_id' => $validated['calendar_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_datetime' => $validated['start_datetime'],
            'end_datetime' => $validated['end_datetime'],
            'location' => $validated['location'] ?? null,
            'mode' => $validated['mode'] ?? 'in_person',
            'contact_id' => $validated['contact_id'] ?? null,
            'product_id' => $validated['product_id'] ?? null,
            'product_variation_id' => $validated['product_variation_id'] ?? null,
            'opportunity_id' => $validated['opportunity_id'] ?? null,
            'status' => $validated['status'] ?? 'scheduled',
            'owner_id' => Auth::id(), // Assigned to current user by default? Or should come from input? User spec says owner_id -- assigned user. Default to auth user is fine for now.
            'created_by' => Auth::id(),
            'source' => 'manual',
        ]);

        return redirect()->back()->with('success', 'Appointment booked successfully.');
    }

    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'calendar_id' => 'sometimes|exists:calendars,id',
            'product_id' => 'nullable|exists:products,id',
            'product_variation_id' => 'nullable|exists:product_variations,id',
            'title' => 'sometimes|string|max:255',
            'start_datetime' => 'sometimes|date',
            'end_datetime' => 'sometimes|date|after:start_datetime',
            'location' => 'nullable|string|max:255',
            'status' => 'sometimes|string',
        ]);

        $event->update($validated);

        return redirect()->back()->with('success', 'Appointment updated successfully.');
    }
}
