<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\InboxAssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InboxAssistantController extends Controller
{
    protected $assistantService;

    public function __construct(InboxAssistantService $assistantService)
    {
        $this->assistantService = $assistantService;
    }

    /**
     * Generate an AI-powered professional reply suggestion.
     */
    public function autowrite(Request $request, Conversation $conversation)
    {
        if ($conversation->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $request->validate([
            'suggestion' => 'required|string|min:3|max:2000',
        ]);

        try {
            $content = $this->assistantService->generateEmailReply(
                $conversation,
                $request->input('suggestion')
            );

            return response()->json([
                'success' => true,
                'content' => $content
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
