<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Calendar;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CalendarController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;

        // Fetch Calendars with Google sync status
        $calendars = Calendar::where('tenant_id', $tenantId)
            ->with(['availability', 'googleConnection'])
            ->get();

        // Fetch Tasks (format as events)
        $taskEvents = Task::where('tenant_id', $tenantId)
            ->whereNotNull('due_at')
            ->get()
            ->map(function($task) {
                return [
                    'id' => 'task-' . $task->id,
                    'title' => $task->title,
                    'start' => $task->due_at,
                    'end' => $task->due_at->copy()->addHour(),
                    'allDay' => false,
                    'type' => 'task',
                    'color' => '#6b7280' // gray for tasks
                ];
            });

        // Fetch Calendar Events
        $calendarEvents = \App\Models\Event::whereHas('calendar', function($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->with(['calendar', 'contact', 'owner', 'product', 'variation'])
          ->get()
          ->map(function($event) {
              return [
                'id' => 'event-' . $event->id,
                'title' => $event->title,
                'start' => $event->start_datetime,
                'end' => $event->end_datetime,
                'allDay' => false, // or derive from duration?
                'type' => 'event',
                'color' => $event->calendar->color ?? '#3b82f6',
                'calendar_id' => $event->calendar_id,
                'details' => $event
              ];
          });

        $allEvents = $taskEvents->concat($calendarEvents);

        $users = User::where('tenant_id', $tenantId)->get();
        $services = \App\Models\Product::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('variations')
            ->orderBy('name')
            ->get();

        return Inertia::render('Calendar/Index', [
            'events' => $allEvents,
            'calendars' => $calendars,
            'users' => $users,
            'services' => $services
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'calendar_type' => 'required|in:user,team,resource,widget,external',
            'owner_id' => 'nullable|exists:users,id',
            'timezone' => 'required|string',
            'color' => 'nullable|string',
            'visibility' => 'required|in:private,team,public',
            'slug' => 'nullable|string|max:255|unique:calendars',
            'slot_duration' => 'nullable|integer|min:5',
            'buffer_time' => 'nullable|integer|min:0',
            'is_public' => 'boolean',
            'is_default' => 'boolean',
            'availability' => 'nullable|array',
            'availability.*.day_of_week' => 'required|integer|min:0|max:6',
            'availability.*.start_time' => 'required|date_format:H:i',
            'availability.*.end_time' => 'required|date_format:H:i|after:availability.*.start_time',
            'product_id' => 'nullable|exists:products,id',
            'product_variation_id' => 'nullable|exists:product_variations,id',
        ]);

        DB::transaction(function () use ($validated) {
            $calendar = Calendar::create([
                'name' => $validated['name'],
                'calendar_type' => $validated['calendar_type'],
                'owner_id' => $validated['owner_id'] ?? null,
                'timezone' => $validated['timezone'],
                'color' => $validated['color'],
                'visibility' => $validated['visibility'],
                'slug' => $validated['slug'] ?? null,
                'slot_duration' => $validated['slot_duration'] ?? 30,
                'buffer_time' => $validated['buffer_time'] ?? 0,
                'is_public' => $validated['is_public'] ?? true,
                'is_default' => $validated['is_default'] ?? false,
                'tenant_id' => Auth::user()->tenant_id, 
                'product_id' => $validated['product_id'] ?? null,
                'product_variation_id' => $validated['product_variation_id'] ?? null,
            ]);

            if (!empty($validated['availability'])) {
                foreach ($validated['availability'] as $slot) {
                    $calendar->availability()->create($slot);
                }
            }
        });

        return redirect()->back()->with('success', 'Calendar created successfully.');
    }

    public function update(Request $request, Calendar $calendar)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'calendar_type' => 'required|in:user,team,resource,widget,external',
            'owner_id' => 'nullable|exists:users,id',
            'timezone' => 'required|string',
            'color' => 'nullable|string',
            'visibility' => 'required|in:private,team,public',
            'slug' => 'nullable|string|max:255|unique:calendars,slug,' . $calendar->id,
            'slot_duration' => 'nullable|integer|min:5',
            'buffer_time' => 'nullable|integer|min:0',
            'is_public' => 'boolean',
            'is_default' => 'boolean',
            'availability' => 'nullable|array',
            'availability.*.day_of_week' => 'required|integer|min:0|max:6',
            'availability.*.start_time' => 'required|date_format:H:i',
            'availability.*.end_time' => 'required|date_format:H:i|after:availability.*.start_time',
            'product_id' => 'nullable|exists:products,id',
            'product_variation_id' => 'nullable|exists:product_variations,id',
        ]);

        DB::transaction(function () use ($validated, $calendar) {
            $calendar->update([
                'name' => $validated['name'],
                'calendar_type' => $validated['calendar_type'],
                'owner_id' => $validated['owner_id'] ?? null,
                'timezone' => $validated['timezone'],
                'color' => $validated['color'],
                'visibility' => $validated['visibility'],
                'slug' => $validated['slug'] ?? null,
                'slot_duration' => $validated['slot_duration'] ?? 30,
                'buffer_time' => $validated['buffer_time'] ?? 0,
                'is_public' => $validated['is_public'] ?? true,
                'is_default' => $validated['is_default'] ?? false,
                'product_id' => $validated['product_id'] ?? null,
                'product_variation_id' => $validated['product_variation_id'] ?? null,
            ]);

            // Sync availability: Delete old, create new
            // Optimization: could diff, but simple delete/create is easier for full replace
            $calendar->availability()->delete();
            
            if (!empty($validated['availability'])) {
                foreach ($validated['availability'] as $slot) {
                    $calendar->availability()->create($slot);
                }
            }
        });

        return redirect()->back()->with('success', 'Calendar updated successfully.');
    }
}
