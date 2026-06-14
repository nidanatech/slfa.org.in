<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Channel;

class ChannelController extends Controller
{
    public function index()
    {
        $tenant = Auth::user()->tenant;
        if (!$tenant) return Inertia::render('Channels/Index', ['channels' => [], 'enabledTypes' => []]);

        $channels = $tenant->channels()
            ->orderByDesc('created_at')
            ->get()
            ->filter(function ($channel) use ($tenant) {
                return $tenant->isChannelEnabled($channel->type);
            })->values();

        // Get enabled channel types for the dialog
        $allTypes = \App\Models\ChannelType::all();
        $enabledTypes = $allTypes->filter(function ($type) use ($tenant) {
            return $tenant->isChannelEnabled($type->id);
        })->values();

        return Inertia::render('Channels/Index', [
            'channels' => $channels,
            'enabledTypes' => $enabledTypes,
            'businessSlug' => $tenant->businessProfile?->slug
        ]);
    }

    public function create()
    {
        $tenant = Auth::user()->tenant;
        $allTypes = \App\Models\ChannelType::all();
        
        $enabledTypes = $allTypes->filter(function ($type) use ($tenant) {
            return $tenant->isChannelEnabled($type->id);
        })->values();

        return Inertia::render('Channels/Create', [
            'enabledTypes' => $enabledTypes
        ]);
    }

    public function store(Request $request)
    {
        // For MVP, just creating manual channel entries or simulating auth
        $validated = $request->validate([
            'type' => 'required|string|in:messenger,instagram,whatsapp,threads,linkedin,tiktok,youtube,google_business,web,api,email,ses,lead_form',
            'name' => 'nullable|string|max:255', // Nullable because SES/API might generate it
            'config' => 'nullable|array'
        ]);
        
        // Ensure name is present for hidden input types
        if (empty($validated['name'])) {
            if ($validated['type'] === 'web') {
                $validated['name'] = 'Web Chat';
            } elseif ($validated['type'] === 'lead_form') {
                $validated['name'] = 'Lead Capture Form';
            }
        }

        $tenant = Auth::user()->tenant;
        if (!$tenant) {
            return back()->with('error', 'No tenant assigned.');
        }

        if ($validated['type'] === 'api') {
            $validated['config'] = [
                'api_token' => \Illuminate\Support\Str::random(64),
                'created_at' => now()->toDateTimeString(),
            ];
        }

        if ($validated['type'] === 'ses') {
            // Validate config
            if (empty($validated['config']['email'])) {
                return back()->withErrors(['config.email' => 'Email address is required.']);
            }

            $email = $validated['config']['email'];
            $domain = substr(strrchr($email, "@"), 1);

            // Enforce Domain First Policy
            $verifiedDomain = \App\Models\SenderIdentity::where('tenant_id', $tenant->id)
                ->where('identity_type', 'domain')
                ->where('identity_value', $domain)
                ->where('status', 'success')
                ->exists();

            if (!$verifiedDomain) {
                return back()->withErrors(['config.email' => "Your domain '{$domain}' is not verified. Please verify it in Settings > Email Senders first."]);
            }

            // Create/Find Sender Identity
            $identity = \App\Models\SenderIdentity::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'identity_type' => 'email',
                    'identity_value' => $email,
                ],
                [
                    'status' => 'success', // Domain is verified, so email is implicitly verified for DKIM sending usually, but SES might still want verification?
                                           // Actually, if domain is verified, we can send from any address.
                                           // But let's trigger verification just in case using SesService if we want to be safe, 
                                           // OR just trust the domain.
                                           // Using SesService here would require injecting it. 
                                           // For now, let's mark as success since Domain is verified.
                   'is_default' => false
                ]
            );

            // Create Email Account (for Warmup/Stats)
            $emailAccount = \App\Models\EmailAccount::updateOrCreate(
                [
                    'user_id' => Auth::id(), // Owner
                    'email_address' => $email,
                ],
                [
                    'tenant_id' => $tenant->id,
                    'provider' => 'ses',
                    'display_name' => $validated['config']['display_name'] ?? $email,
                    'status' => 'active',
                    'sync_enabled' => false, // No receiving for now
                    'warmup_enabled' => false,
                ]
            );

            // Link to Channel
            if (in_array($validated['type'], ['gmail', 'outlook', 'ses'])) {
                $validated['config']['email_account_id'] = $emailAccount->id;
                $displayName = $validated['config']['display_name'] ?? null;
                $validated['name'] = $displayName ? "{$displayName} ({$email})" : $email;
            }
        }

        $tenant->channels()->create($validated);

        return redirect()->route('channels.index');
    }

    public function show(\App\Models\Channel $channel)
    {
        // Security check
        if ($channel->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        // Redirect WordPress channels to their specialized controller
        if ($channel->type === 'wordpress') {
            return redirect()->route('channels.wordpress.show', $channel->id);
        }

        $data = ['channel' => $channel];

        // Add email account for email/ses/gmail/outlook channels
        if (in_array($channel->type, ['email', 'ses', 'gmail', 'outlook']) && !empty($channel->config['email_account_id'])) {
            $emailAccount = \App\Models\EmailAccount::find($channel->config['email_account_id']);
            if ($emailAccount) {
                $data['emailAccount'] = $emailAccount;
            }
        }

        // Add custom fields and pipelines for lead_form channels
        if ($channel->type === 'lead_form') {
            $data['customFields'] = \App\Models\CustomField::where('tenant_id', Auth::user()->tenant_id)->get();
            $data['pipelines'] = \App\Models\Pipeline::where('tenant_id', Auth::user()->tenant_id)->with('stages')->get();
        }

        $data['businessSlug'] = Auth::user()->tenant->businessProfile?->slug;

        return Inertia::render('Channels/Show', $data);
    }

    public function update(Request $request, Channel $channel)
    {
        if ($channel->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'config' => 'nullable|array'
        ]);

        if (isset($validated['config'])) {
            $existingConfig = $channel->config ?? [];
            $validated['config'] = array_merge($existingConfig, $validated['config']);
        }

        $channel->update($validated);

        return back()->with('success', 'Channel updated.');
    }

    public function destroy(Channel $channel)
    {
        if ($channel->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }
        
        $channel->delete();
        return redirect()->route('channels.index');
    }
}
