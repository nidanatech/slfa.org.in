<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return back();
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
    
    // Optional: Full index page for notifications if needed later
    public function index(Request $request)
    {
        // Not used for dropdown, but could be for a "See All" page
        // For now, dropdown is handled via HandleInertiaRequests sharing data
    }
}
