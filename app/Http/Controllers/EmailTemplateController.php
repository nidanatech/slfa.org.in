<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $templates = EmailTemplate::where(function ($query) {
            $query->where('tenant_id', auth()->user()->tenant_id)
                  ->orWhere('is_system', true);
        })
        ->latest()
        ->get()
        ->map(function ($template) {
            return [
                'id' => $template->id,
                'name' => $template->name,
                'category' => $template->category,
                'description' => $template->description,
                'thumbnail_url' => $template->thumbnail_url,
                'is_system' => $template->is_system,
                'is_default' => $template->is_default,
                'created_at' => $template->created_at,
            ];
        });

        return Inertia::render('Marketing/EmailTemplates/Index', [
            'templates' => $templates,
        ]);
    }

    public function create()
    {
        $customFields = \App\Models\CustomField::where('tenant_id', auth()->user()->tenant_id)
            ->get(['name', 'label'])
            ->toArray();

        return Inertia::render('Marketing/EmailTemplates/Create', [
            'customFields' => $customFields,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'content_html' => 'required|string',
            'content_text' => 'nullable|string',
        ]);

        $template = EmailTemplate::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'category' => $validated['category'] ?? 'custom',
            'description' => $validated['description'],
            'content_html' => $validated['content_html'],
            'content_text' => $validated['content_text'] ?? strip_tags($validated['content_html']),
            'is_system' => false,
        ]);

        return redirect()->route('marketing.email-templates.index')
            ->with('success', 'Template created successfully!');
    }

    public function show(EmailTemplate $template)
    {
        $this->authorize('view', $template);

        return response()->json([
            'id' => $template->id,
            'name' => $template->name,
            'category' => $template->category,
            'description' => $template->description,
            'content_html' => $template->content_html,
            'content_text' => $template->content_text,
            'is_system' => $template->is_system,
        ]);
    }

    public function edit(EmailTemplate $template)
    {
        $this->authorize('update', $template);

        return Inertia::render('Marketing/EmailTemplates/Edit', [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'category' => $template->category,
                'description' => $template->description,
                'content_html' => $template->content_html,
                'content_text' => $template->content_text,
            ],
        ]);
    }

    public function update(Request $request, EmailTemplate $template)
    {
        $this->authorize('update', $template);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'content_html' => 'required|string',
            'content_text' => 'nullable|string',
        ]);

        $template->update([
            'name' => $validated['name'],
            'category' => $validated['category'] ?? 'custom',
            'description' => $validated['description'],
            'content_html' => $validated['content_html'],
            'content_text' => $validated['content_text'] ?? strip_tags($validated['content_html']),
        ]);

        return redirect()->route('marketing.email-templates.index')
            ->with('success', 'Template updated successfully!');
    }

    public function destroy(EmailTemplate $template)
    {
        $this->authorize('delete', $template);

        if ($template->is_system) {
            return back()->with('error', 'Cannot delete system templates.');
        }

        $template->delete();

        return redirect()->route('marketing.email-templates.index')
            ->with('success', 'Template deleted successfully!');
    }
}
