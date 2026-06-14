<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Models\Contact;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;


class BookingController extends Controller
{
    public function show($slug)
    {
        $calendar = Calendar::where('slug', $slug)
            ->where('is_public', true)
            ->with(['owner', 'availability'])
            ->firstOrFail();

        return Inertia::render('Booking/Show', [
            'calendar' => $calendar,
            'owner' => $calendar->owner,
        ]);
    }

    public function slots($slug, Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'timezone' => 'required|string', // User's timezone
        ]);

        $calendar = Calendar::where('slug', $slug)
            ->where('is_public', true)
            ->with(['availability', 'events'])
            ->firstOrFail();

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        $userTimezone = $request->timezone;       // e.g., 'America/New_York'
        $calendarTimezone = $calendar->timezone;  // e.g., 'UTC'

        // 1. Get existing events in range (adjusted for buffer if needed)
        // We fetch events based on UTC stored in DB usually
        $events = $calendar->events()
            ->whereBetween('start_datetime', [$startDate, $endDate])
            ->get();

        // 2. Construct available slots
        $slots = [];
        $duration = $calendar->slot_duration ?? 30; // minutes
        $buffer = $calendar->buffer_time ?? 0;

        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dayOfWeek = $currentDate->dayOfWeek; // 0=Sunday
            
            // Check availability for this day of week
            // Availability is usually stored in specific timezone or UTC? 
            // Let's assume Availability is stored in Calendar's Timezone context.
            // So we need to convert "Current Date" (which is just a date) to Calendar Timezone intervals.
            
            $availabilities = $calendar->availability->where('day_of_week', $dayOfWeek);

            foreach ($availabilities as $rule) {
                // Rule times are typically '09:00:00' string
                // Create Carbon instances in Calendar Timezone
                $ruleStart = Carbon::parse($currentDate->format('Y-m-d') . ' ' . $rule->start_time, $calendarTimezone);
                $ruleEnd = Carbon::parse($currentDate->format('Y-m-d') . ' ' . $rule->end_time, $calendarTimezone);

                // Generate slots
                $slotStart = $ruleStart->copy();

                while ($slotStart->copy()->addMinutes($duration)->lte($ruleEnd)) {
                    $slotEnd = $slotStart->copy()->addMinutes($duration);

                    // Check collisions in UTC
                    // Convert slot to UTC for DB comparison
                    $utcSlotStart = $slotStart->copy()->setTimezone('UTC');
                    $utcSlotEnd = $slotEnd->copy()->setTimezone('UTC');

                    $isBlocked = $events->contains(function ($event) use ($utcSlotStart, $utcSlotEnd, $buffer) {
                        // Event times are UTC from DB
                        $eventStart = Carbon::parse($event->start_datetime);
                        $eventEnd = Carbon::parse($event->end_datetime)->addMinutes($buffer); // Add buffer to event end
                        
                        // Check overlap
                        return $utcSlotStart->lt($eventEnd) && $utcSlotEnd->gt($eventStart);
                    });

                    if (!$isBlocked) {
                        $slots[] = [
                            'date' => $currentDate->format('Y-m-d'),
                            'start' => $slotStart->copy()->setTimezone($userTimezone)->format('H:i'),
                            'end' => $slotEnd->copy()->setTimezone($userTimezone)->format('H:i'),
                            'utc_start' => $utcSlotStart->toIso8601String(),
                            'utc_end' => $utcSlotEnd->toIso8601String(),
                        ];
                    }

                    $slotStart->addMinutes($duration + $buffer); // Add buffer between slots too? Or just after events?
                    // Usually buffer is "Time after appointment".
                    // If buffer is 10m, and slot is 9:00-9:30. Next slot can start at 9:40.
                    // Implementation: addMinutes($duration + $buffer)
                }
            }

            $currentDate->addDay();
        }

        return response()->json($slots);
    }

    public function store($slug, Request $request)
    {
        $calendar = Calendar::where('slug', $slug)->with(['product', 'variation', 'tenant.stripeConfig'])->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'notes' => 'nullable|string',
            'start_time' => 'required|date', // ISO8601 UTC
        ]);

        DB::beginTransaction();
        try {
            // 1. Find or Create Contact
            $email = $validated['email'];
            $contact = Contact::where('tenant_id', $calendar->tenant_id)
                ->where(function($q) use ($email) {
                    $q->whereJsonContains('emails', [['value' => $email]])
                      ->orWhere('emails', 'like', '%' . $email . '%');
                })
                ->first();

            if (!$contact) {
                // Split name for better storage
                $nameParts = Contact::splitName($validated['name']);
                
                $contact = Contact::create([
                    'tenant_id' => $calendar->tenant_id,
                    'name' => $validated['name'],
                    'first_name' => $nameParts['first_name'],
                    'last_name' => $nameParts['last_name'],
                    'emails' => [['type' => 'work', 'value' => $email]],
                    'phones' => $validated['phone'] ? [['type' => 'mobile', 'value' => $validated['phone']]] : null,
                    'owner_id' => $calendar->owner_id,
                    'external_id' => (string) Str::uuid(),
                    'contact_type' => 'lead'
                ]);
            }

            // 2. Prepare Event
            $startTime = Carbon::parse($validated['start_time']);
            $endTime = $startTime->copy()->addMinutes($calendar->slot_duration ?? 30);

            // Check for payment requirement
            $requiresPayment = false;
            $stripePriceId = null;
            $amount = 0;

            if ($calendar->tenant->stripeConfig && $calendar->tenant->stripeConfig->is_active) {
                if ($calendar->variation && $calendar->variation->price > 0) {
                    $requiresPayment = true;
                    $stripePriceId = $calendar->variation->stripe_price_id;
                    $amount = $calendar->variation->price;
                } elseif ($calendar->product && $calendar->product->price > 0) {
                    $requiresPayment = true;
                    $stripePriceId = $calendar->product->stripe_price_id;
                    $amount = $calendar->product->price;
                }
            }

            $event = $calendar->events()->create([
                'title' => 'Meeting with ' . $contact->name,
                'start_datetime' => $startTime,
                'end_datetime' => $endTime,
                'contact_id' => $contact->id,
                'description' => $validated['notes'],
                'tenant_id' => $calendar->tenant_id,
                'owner_id' => $calendar->owner_id,
                'status' => $requiresPayment ? 'pending' : 'confirmed',
                'payment_status' => $requiresPayment ? 'pending' : 'n/a',
                'product_id' => $calendar->product_id,
                'product_variation_id' => $calendar->product_variation_id,
                'source' => 'widget'
            ]);

            if ($requiresPayment && $stripePriceId) {
                $stripeService = app(\App\Services\TenantStripeService::class);
                $session = $stripeService->createCheckoutSession(
                    $calendar->tenant,
                    [['price' => $stripePriceId, 'quantity' => 1]],
                    route('booking.show', $slug) . '?status=success&session_id={CHECKOUT_SESSION_ID}',
                    route('booking.show', $slug) . '?status=cancelled',
                    [
                        'event_id' => $event->id,
                        'contact_id' => $contact->id,
                        'type' => 'booking'
                    ]
                );

                $event->update(['stripe_session_id' => $session->id]);
                DB::commit();

                return Inertia::location($session->url);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Appointment booked successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Booking store error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
