<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $tickets = SupportTicket::where('tenant_id', $tenantId)
            ->with(['messages' => function($q) {
                $q->latest()->limit(1);
            }])
            ->latest()
            ->get();

        return Inertia::render('Support/Index', [
            'tickets' => $tickets
        ]);
    }

    public function create()
    {
        return Inertia::render('Support/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'category' => 'required|string',
        ]);

        $ticket = SupportTicket::create([
            'tenant_id' => $request->user()->tenant_id,
            'user_id' => $request->user()->id,
            'subject' => $request->subject,
            'priority' => $request->priority,
            'category' => $request->category,
            'status' => 'open',
        ]);

        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $request->message,
            'is_internal' => false,
        ]);

        return redirect()->route('support.show', $ticket->id)->with('success', 'Support ticket created successfully.');
    }

    public function show(SupportTicket $ticket)
    {
        // Ensure user belongs to the same tenant as the ticket
        if ($ticket->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $ticket->load(['messages.user', 'assignedTo']);

        return Inertia::render('Support/Show', [
            'ticket' => $ticket
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        if ($ticket->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $request->validate([
            'message' => 'required|string',
        ]);

        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $request->message,
            'is_internal' => false,
        ]);

        // If ticket was resolved, maybe reopen it? Or keep it as is.
        // For now, just keep status as is or update if it was resolved
        if ($ticket->status === 'resolved') {
            $ticket->update(['status' => 'open']);
        }

        return back()->with('success', 'Reply sent.');
    }
}
