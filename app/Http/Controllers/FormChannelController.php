<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Services\ChannelService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class FormChannelController extends Controller
{
    /**
     * Show the form builder/editor
     */
    public function create()
    {
        // Fetch custom fields for the builder
        $customFields = \App\Models\CustomField::where('tenant_id', auth()->user()->tenant_id)->get();
        $pipelines = \App\Models\Pipeline::where('tenant_id', auth()->user()->tenant_id)->with('stages')->get();
        $products = \App\Models\Product::where('tenant_id', auth()->user()->tenant_id)->where('is_active', true)->with('variations')->get();

        return Inertia::render('Channels/Forms/Edit', [
            'customFields' => $customFields,
            'pipelines' => $pipelines,
            'products' => $products,
            'mode' => 'create'
        ]);
    }

    /**
     * Store a new form channel
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255', // We will generate if empty
            'description' => 'nullable|string',
            'branding' => 'nullable|array',
            'fields' => 'required|array',
            'actions' => 'nullable|array',
            'submit_button_text' => 'nullable|string',
            'success_message' => 'nullable|string',
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['title']);
        // Ensure slug uniqueness within tenant scope (or globally? public forms usually need global or tenant-prefix)
        // For simplicity, we'll append random string if exists, or rely on UUID mainly.
        // Actually, let's use UUID for the route, slug is cosmetic but good for SEO.
        
        $config = [
            'title' => $validated['title'],
            'slug' => $slug,
            'description' => $validated['description'] ?? '',
            'branding' => $validated['branding'] ?? [],
            'fields' => $validated['fields'],
            'actions' => $validated['actions'] ?? [],
            'submit_button_text' => $validated['submit_button_text'] ?? 'Submit',
            'success_message' => $validated['success_message'] ?? 'Thank you! We will contact you soon.',
            'api_token' => \Illuminate\Support\Str::random(64),
        ];

        $channel = Channel::create([
            'tenant_id' => auth()->user()->tenant_id,
            'type' => 'lead_form',
            'name' => $validated['title'],
            'config' => $config,
            'is_active' => true
        ]);

        return redirect()->route('channels.index')->with('success', 'Lead form created successfully.');
    }

    public function edit(Channel $channel)
    {
        // Policy check?
        $customFields = \App\Models\CustomField::where('tenant_id', auth()->user()->tenant_id)->get();
        $pipelines = \App\Models\Pipeline::where('tenant_id', auth()->user()->tenant_id)->with('stages')->get();
        $products = \App\Models\Product::where('tenant_id', auth()->user()->tenant_id)->where('is_active', true)->with('variations')->get();

        $businessSlug = \App\Models\BusinessProfile::where('tenant_id', auth()->user()->tenant_id)->value('slug');

        return Inertia::render('Channels/Forms/Edit', [
            'form' => $channel,
            'customFields' => $customFields,
            'pipelines' => $pipelines,
            'products' => $products,
            'businessSlug' => $businessSlug,
            'mode' => 'edit'
        ]);
    }

    public function update(Request $request, Channel $channel)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'branding' => 'nullable|array',
            'fields' => 'required|array',
            'actions' => 'nullable|array',
            'submit_button_text' => 'nullable|string',
            'success_message' => 'nullable|string',
        ]);

        $config = $channel->config;
        $config['title'] = $validated['title'];
        $config['description'] = $validated['description'];
        $config['branding'] = $validated['branding'];
        $config['fields'] = $validated['fields'];
        $config['actions'] = $validated['actions'];
        $config['submit_button_text'] = $validated['submit_button_text'];
        $config['success_message'] = $validated['success_message'];
        if ($validated['slug']) $config['slug'] = $validated['slug'];
    
    // Ensure api_token exists
    if (!isset($config['api_token'])) {
        $config['api_token'] = \Illuminate\Support\Str::random(64);
    }

        $channel->update([
            'name' => $validated['title'],
            'config' => $config
        ]);

        return redirect()->back()->with('success', 'Form updated.');
    }
}
