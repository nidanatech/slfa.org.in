<?php

namespace App\Http\Controllers;

use App\Models\GoogleCalendarConnection;
use App\Models\Calendar;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class GoogleCalendarController extends Controller
{
    protected GoogleCalendarService $googleService;

    public function __construct(GoogleCalendarService $googleService)
    {
        $this->googleService = $googleService;
    }

    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle(Request $request)
    {
        $request->session()->put('google_calendar_state', [
            'calendar_id' => $request->input('calendar_id'),
        ]);

        $authUrl = $this->googleService->getAuthUrl();
        return redirect($authUrl);
    }

    /**
     * Handle OAuth callback
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('calendar.index')
                ->with('error', 'Google authorization was cancelled.');
        }

        try {
            $code = $request->input('code');
            $token = $this->googleService->handleCallback($code);
            
            $userEmail = $this->googleService->getUserEmail($token['access_token']);
            
            $state = $request->session()->get('google_calendar_state');
            $calendarId = $state['calendar_id'] ?? null;

            // Store connection (without linking to specific calendar yet)
            GoogleCalendarConnection::create([
                'user_id' => Auth::id(),
                'tenant_id' => Auth::user()->tenant_id,
                'calendar_id' => $calendarId,
                'google_email' => $userEmail,
                'google_calendar_id' => 'primary', // Default, will be updated when user selects
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($token['expires_in']),
                'sync_enabled' => false, // Disabled until user selects calendar
            ]);

            $request->session()->forget('google_calendar_state');

            return redirect()->route('calendar.index')
                ->with('success', 'Google account connected! Now select which calendar to sync.');
                
        } catch (\Exception $e) {
            Log::error('Google OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('calendar.index')
                ->with('error', 'Failed to connect Google account: ' . $e->getMessage());
        }
    }

    /**
     * List user's Google calendars
     */
    public function listCalendars(Request $request)
    {
        $connectionId = $request->input('connection_id');
        
        try {
            $connection = GoogleCalendarConnection::where('user_id', Auth::id())
                ->findOrFail($connectionId);

            $calendars = $this->googleService->listCalendars($connection);

            return response()->json([
                'calendars' => $calendars
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to list Google calendars', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to fetch calendars: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Connect local calendar to Google calendar
     */
    public function connect(Request $request)
    {
        $validated = $request->validate([
            'connection_id' => 'required|exists:google_calendar_connections,id',
            'calendar_id' => 'required|exists:calendars,id',
            'google_calendar_id' => 'required|string',
            'google_calendar_name' => 'nullable|string',
        ]);

        try {
            $connection = GoogleCalendarConnection::where('user_id', Auth::id())
                ->findOrFail($validated['connection_id']);

            $connection->update([
                'calendar_id' => $validated['calendar_id'],
                'google_calendar_id' => $validated['google_calendar_id'],
                'google_calendar_name' => $validated['google_calendar_name'],
                'sync_enabled' => true,
            ]);

            return redirect()->back()
                ->with('success', 'Calendar connected to Google Calendar successfully!');
                
        } catch (\Exception $e) {
            Log::error('Failed to connect calendar to Google', [
                'connection_id' => $validated['connection_id'],
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to connect calendar: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect Google calendar
     */
    public function disconnect(GoogleCalendarConnection $connection)
    {
        if ($connection->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            // Update all events to remove Google sync
            $connection->events()->update([
                'google_event_id' => null,
                'google_calendar_connection_id' => null,
                'last_synced_at' => null,
            ]);

            $connection->delete();

            return redirect()->back()
                ->with('success', 'Google Calendar disconnected successfully.');
                
        } catch (\Exception $e) {
            Log::error('Failed to disconnect Google calendar', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to disconnect: ' . $e->getMessage());
        }
    }

    /**
     * Manually trigger sync for a calendar
     */
    public function syncNow(Calendar $calendar)
    {
        if ($calendar->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        try {
            $connection = $calendar->googleConnection;
            
            if (!$connection || !$connection->sync_enabled) {
                return redirect()->back()
                    ->with('error', 'This calendar is not connected to Google Calendar.');
            }

            // Dispatch sync job for all events in this calendar
            $events = $calendar->events()->whereNull('google_event_id')->get();
            
            foreach ($events as $event) {
                \App\Jobs\SyncEventToGoogle::dispatch($event, 'create');
            }

            return redirect()->back()
                ->with('success', "Syncing {$events->count()} events to Google Calendar...");
                
        } catch (\Exception $e) {
            Log::error('Manual sync failed', [
                'calendar_id' => $calendar->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }
}
