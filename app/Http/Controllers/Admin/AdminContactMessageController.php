<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactMessage::latest();
        
        if ($request->filled('unread_only')) {
            $query->where('read', false);
        }

        return Inertia::render('Admin/ContactMessages/Index', [
            'messages' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['unread_only']),
        ]);
    }

    public function markRead(ContactMessage $message)
    {
        $message->update(['read' => true]);
        return back()->with('success', 'Message marked as read.');
    }

    public function destroy(ContactMessage $message)
    {
        $message->delete();
        return back()->with('success', 'Message deleted.');
    }
}
