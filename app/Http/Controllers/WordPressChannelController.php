<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;

class WordPressChannelController extends Controller
{
    public function create()
    {
        return Inertia::render('Channels/WordPress/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'website_url' => 'required|url',
        ]);

        // Create the channel
        $channel = Channel::create([
            'tenant_id' => $request->user()->tenant_id,
            'type' => 'wordpress',
            'name' => $request->name,
            'config' => [
                'website_url' => $request->website_url,
                'api_secret' => Str::random(32), // Secret for the plugin to authenticate
                'verification_token' => Str::random(64),
                'verified_at' => null,
            ],
            'is_active' => true,
        ]);

        return redirect()->route('channels.wordpress.show', $channel->id);
    }

    public function show(Channel $channel)
    {
        // Ensure access rights
        if ($channel->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        return Inertia::render('Channels/WordPress/Show', [
            'channel' => $channel,
            'domain' => request()->getSchemeAndHttpHost(),
        ]);
    }

    // Called by the frontend "Verify" button
    public function verifyConnection(Channel $channel)
    {
        if ($channel->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if (empty($channel->config['website_url'])) {
            return back()->with('error', 'Website URL is not configured for this channel.');
        }

        $url = rtrim($channel->config['website_url'], '/') . '/wp-json/sydkic/v1/verify';
        
        try {
            $response = Http::timeout(10)->post($url, [
                'verification_token' => $channel->config['api_secret'],
                'channel_id' => $channel->public_id,
            ]);

            if ($response->successful()) {
                $config = $channel->config;
                $config['verified_at'] = now();
                $config['plugin_version'] = $response->json('version');
                $channel->update(['config' => $config]);

                return back()->with('success', 'Connection verified successfully!');
            }

            return back()->with('error', 'Could not verify connection. Plugin returned error: ' . $response->body());

        } catch (\Exception $e) {
            return back()->with('error', 'Connection failed: ' . $e->getMessage());
        }
    }

    // API Endpoint: Called by WP Plugin to check status
    // GET /api/channels/wordpress/{channel}/status
    public function status($publicId)
    {
        $channel = Channel::where('public_id', $publicId)->firstOrFail();
        
        // Simple authentication via header or query param could be added here
        // For now, public_id serves as the identifier

        // Get Active Bot
        // Assuming Logic: Last active bot or specific bot assignment
        // Placeholder logic
        $botName = "Support Assistant"; 
        $botActive = true;

        return response()->json([
            'connected' => true,
            'channel_name' => $channel->name,
            'bot' => [
                'name' => $botName,
                'active' => $botActive,
                'avatar' => null,
            ],
            'settings' => [
                'primary_color' => '#1F2A44', // Could fetch from tenant settings
            ],
        ]);
    }

    public function destroy(Channel $channel)
    {
        if ($channel->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $channel->delete();

        return redirect()->route('channels.index')->with('success', 'WordPress channel deleted successfully.');
    }

    public function updateMessageStatus(Request $request, $publicId, \App\Models\Message $message)
    {
        $request->validate([
            'status' => 'required|in:delivered,read',
        ]);

        $channel = Channel::where('public_id', $publicId)->firstOrFail();
        
        if ($message->conversation->channel_id !== $channel->id) {
            abort(403);
        }

        $updateData = ['status' => $request->status];
        if ($request->status === 'delivered') {
            $updateData['delivered_at'] = now();
        } elseif ($request->status === 'read') {
            $updateData['seen_at'] = now();
        }

        $message->update($updateData);

        return response()->json(['success' => true]);
    }

    public function editMessage(Request $request, $publicId, \App\Models\Message $message)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $channel = Channel::where('public_id', $publicId)->firstOrFail();
        
        if ($message->conversation->channel_id !== $channel->id) {
            abort(403);
        }

        $message->update([
            'content' => $request->content,
            'is_edited' => true,
            'edited_at' => now(),
            'status' => 'edited',
        ]);

        return response()->json(['success' => true]);
    }
}
