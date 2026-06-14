<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupportController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Support/Index', [
            'tickets' => SupportTicket::with(['tenant', 'user'])->latest()->get()
        ]);
    }

    public function show(SupportTicket $ticket)
    {
        return Inertia::render('Admin/Support/Show', [
            'ticket' => $ticket->load(['tenant', 'user', 'messages.user', 'assignedTo'])
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $request->validate([
            'message' => 'required|string',
            'is_internal' => 'boolean'
        ]);

        $message = $ticket->messages()->create([
            'user_id' => auth()->id(),
            'message' => $request->message,
            'is_internal' => $request->is_internal ?? false,
        ]);

        // Automatically mark as in_progress if as staff replies
        if (!$request->is_internal && $ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        return back()->with('success', 'Reply sent successfully.');
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $request->validate([
            'status' => 'required|string|in:open,in_progress,pending,resolved,closed',
            'priority' => 'string|in:low,medium,high,urgent'
        ]);

        $ticket->update($request->only(['status', 'priority']));

        return back()->with('success', 'Ticket updated successfully.');
    }
}
