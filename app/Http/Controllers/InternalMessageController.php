<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Contact;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InternalMessageController extends Controller
{
    /**
     * Create a new internal conversation and send first message
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipient_id' => 'required|integer',
            'recipient_type' => 'required|in:contact,user',
            'message' => 'required|string|min:1',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $tenantId = auth()->user()->tenant_id;

        // Handle different recipient types
        if ($validated['recipient_type'] === 'user') {
            // Messaging a team member - create a virtual contact or use user directly
            $recipientUser = \App\Models\User::where('id', $validated['recipient_id'])
                ->where('tenant_id', $tenantId)
                ->firstOrFail();
            
            // Check if contact exists for this user, if not create one
            $contact = Contact::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'email' => $recipientUser->email,
                ],
                [
                    'name' => $recipientUser->name,
                    'type' => 'team_member',
                ]
            );
        } else {
            // Verify contact belongs to tenant
            $contact = Contact::where('id', $validated['recipient_id'])
                ->where('tenant_id', $tenantId)
                ->firstOrFail();
        }

        DB::beginTransaction();
        try {
            // Create internal conversation
            $conversation = Conversation::create([
                'tenant_id' => $tenantId,
                'contact_id' => $contact->id,
                'channel_id' => null, // No channel for internal conversations
                'conversation_type' => 'internal',
                'initiated_by' => auth()->id(),
                'assigned_to' => $validated['assigned_to'] ?? auth()->id(),
                'status' => 'open',
                'last_message_at' => now(),
            ]);

            // Create first message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'user',
                'content' => $validated['message'],
                'direction' => 'outbound',
                'meta' => [
                    'sender_id' => auth()->id(),
                    'status' => 'sent',
                ],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'conversation' => $conversation->load(['contact', 'initiatedBy', 'assignee']),
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Failed to create conversation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a message in an existing internal conversation
     */
    public function send(Request $request, $conversationId)
    {
        $validated = $request->validate([
            'message' => 'required|string|min:1',
        ]);

        $tenantId = auth()->user()->tenant_id;

        // Find conversation and verify it's internal and belongs to tenant
        $conversation = Conversation::where('id', $conversationId)
            ->whereHas('contact', function($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->where('conversation_type', 'internal')
            ->firstOrFail();

        // Create message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'user',
            'content' => $validated['message'],
            'direction' => 'outbound',
            'meta' => [
                'sender_id' => auth()->id(),
                'status' => 'sent',
            ],
        ]);

        // Update conversation last message time
        $conversation->update([
            'last_message_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * Get contacts and users for new message modal (searchable)
     */
    public function getRecipients(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $search = $request->get('search', '');
        $type = $request->get('type', 'all'); // 'all', 'contacts', 'users'

        $results = [];

        // Get Contacts
        if (in_array($type, ['all', 'contacts'])) {
            $contacts = Contact::where('tenant_id', $tenantId)
                ->when($search, function($q) use ($search) {
                    $q->where(function($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
                })
                ->select('id', 'name', 'email', 'phone', 'avatar_url')
                ->limit(10)
                ->get()
                ->map(function($contact) {
                    return [
                        'id' => $contact->id,
                        'type' => 'contact',
                        'name' => $contact->name,
                        'email' => $contact->email,
                        'phone' => $contact->phone,
                        'avatar_url' => $contact->avatar_url,
                    ];
                });

            $results = array_merge($results, $contacts->toArray());
        }

        // Get Users (Team Members)
        if (in_array($type, ['all', 'users'])) {
            $users = \App\Models\User::where('tenant_id', $tenantId)
                ->where('id', '!=', auth()->id()) // Exclude current user
                ->when($search, function($q) use ($search) {
                    $q->where(function($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->select('id', 'name', 'email')
                ->limit(10)
                ->get()
                ->map(function($user) {
                    return [
                        'id' => $user->id,
                        'type' => 'user',
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => null,
                        'avatar_url' => null,
                    ];
                });

            $results = array_merge($results, $users->toArray());
        }

        return response()->json($results);
    }
}
