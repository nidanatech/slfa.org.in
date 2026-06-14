<?php

namespace App\Services;

use App\Models\GoogleCalendarConnection;
use App\Models\Event;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GoogleCalendarService
{
    protected Google_Client $client;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect_uri'));
        $this->client->addScope(Google_Service_Calendar::CALENDAR);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    /**
     * Get authorization URL for OAuth flow
     */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Handle OAuth callback and exchange code for tokens
     */
    public function handleCallback(string $code): array
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        
        if (isset($token['error'])) {
            throw new \Exception('Error fetching access token: ' . $token['error']);
        }

        return $token;
    }

    /**
     * Set access token for API calls
     */
    public function setAccessToken(GoogleCalendarConnection $connection): void
    {
        if ($connection->needsTokenRefresh()) {
            $this->refreshAccessToken($connection);
        }

        $this->client->setAccessToken($connection->access_token);
    }

    /**
     * Refresh expired access token
     */
    public function refreshAccessToken(GoogleCalendarConnection $connection): void
    {
        $this->client->setAccessToken($connection->access_token);
        
        if ($this->client->isAccessTokenExpired()) {
            $newToken = $this->client->fetchAccessTokenWithRefreshToken($connection->refresh_token);
            
            if (isset($newToken['error'])) {
                Log::error('Google token refresh failed', [
                    'connection_id' => $connection->id,
                    'error' => $newToken['error']
                ]);
                throw new \Exception('Failed to refresh token: ' . $newToken['error']);
            }

            $connection->update([
                'access_token' => $newToken['access_token'],
                'expires_at' => now()->addSeconds($newToken['expires_in']),
            ]);
        }
    }

    /**
     * Get list of user's Google calendars
     */
    public function listCalendars(GoogleCalendarConnection $connection): array
    {
        $this->setAccessToken($connection);
        
        $service = new Google_Service_Calendar($this->client);
        $calendarList = $service->calendarList->listCalendarList();
        
        return array_map(function($calendar) {
            return [
                'id' => $calendar->getId(),
                'summary' => $calendar->getSummary(),
                'description' => $calendar->getDescription(),
                'primary' => $calendar->getPrimary(),
                'backgroundColor' => $calendar->getBackgroundColor(),
            ];
        }, $calendarList->getItems());
    }

    /**
     * Create event in Google Calendar
     */
    public function createEvent(GoogleCalendarConnection $connection, Event $event): string
    {
        $this->setAccessToken($connection);
        
        $service = new Google_Service_Calendar($this->client);
        $googleEvent = $this->buildGoogleEvent($event);
        
        try {
            $createdEvent = $service->events->insert($connection->google_calendar_id, $googleEvent);
            
            Log::info('Google Calendar event created', [
                'event_id' => $event->id,
                'google_event_id' => $createdEvent->getId()
            ]);
            
            return $createdEvent->getId();
        } catch (\Exception $e) {
            Log::error('Failed to create Google Calendar event', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update event in Google Calendar
     */
    public function updateEvent(GoogleCalendarConnection $connection, Event $event): void
    {
        if (!$event->google_event_id) {
            throw new \Exception('Event does not have a Google event ID');
        }

        $this->setAccessToken($connection);
        
        $service = new Google_Service_Calendar($this->client);
        $googleEvent = $this->buildGoogleEvent($event);
        
        try {
            $service->events->update(
                $connection->google_calendar_id,
                $event->google_event_id,
                $googleEvent
            );
            
            Log::info('Google Calendar event updated', [
                'event_id' => $event->id,
                'google_event_id' => $event->google_event_id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update Google Calendar event', [
                'event_id' => $event->id,
                'google_event_id' => $event->google_event_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete event from Google Calendar
     */
    public function deleteEvent(GoogleCalendarConnection $connection, string $googleEventId): void
    {
        $this->setAccessToken($connection);
        
        $service = new Google_Service_Calendar($this->client);
        
        try {
            $service->events->delete($connection->google_calendar_id, $googleEventId);
            
            Log::info('Google Calendar event deleted', [
                'google_event_id' => $googleEventId
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete Google Calendar event', [
                'google_event_id' => $googleEventId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Build Google Calendar event from app event
     */
    protected function buildGoogleEvent(Event $event): Google_Service_Calendar_Event
    {
        $googleEvent = new Google_Service_Calendar_Event();
        $googleEvent->setSummary($event->title);
        $googleEvent->setDescription($event->description);
        $googleEvent->setLocation($event->location);

        $start = new Google_Service_Calendar_EventDateTime();
        $start->setDateTime($event->start_datetime->toRfc3339String());
        $start->setTimeZone($event->timezone ?? config('app.timezone'));
        $googleEvent->setStart($start);

        $end = new Google_Service_Calendar_EventDateTime();
        $end->setDateTime($event->end_datetime->toRfc3339String());
        $end->setTimeZone($event->timezone ?? config('app.timezone'));
        $googleEvent->setEnd($end);

        if ($event->meeting_link) {
            $googleEvent->setHangoutLink($event->meeting_link);
        }

        return $googleEvent;
    }

    /**
     * Get user email from access token
     */
    public function getUserEmail(string $accessToken): string
    {
        $this->client->setAccessToken($accessToken);
        
        $oauth2 = new \Google_Service_Oauth2($this->client);
        $userInfo = $oauth2->userinfo->get();
        
        return $userInfo->email;
    }
}
