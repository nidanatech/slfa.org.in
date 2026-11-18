<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Event;
use App\Models\NewsPost;
use App\Models\Payment;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_members' => User::where('account_status', 'active')->count(),
                'pending_approvals' => User::where('account_status', 'pending')->count(),
                'upcoming_events' => Event::upcoming()->count(),
                'total_revenue' => Payment::where('status', 'paid')->sum('amount'),
            ],
            'recent_applications' => User::with('profile')->where('account_status', 'pending')->latest()->take(5)->get(),
            'recent_payments' => Payment::with('user')->latest()->take(5)->get(),
        ]);
    }
}
