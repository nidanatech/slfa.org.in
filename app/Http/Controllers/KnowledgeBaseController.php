<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBase;
use App\Models\KnowledgeSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class KnowledgeBaseController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id ?? Auth::user()->tenant->id ?? null;
        
        if (!$tenantId) {
            return Inertia::render('Bots/KnowledgeBase', [
                'knowledgeBases' => []
            ]);
        }

        $knowledgeBases = KnowledgeBase::where('tenant_id', $tenantId)
            ->withCount('sources')
            ->with(['sources' => function($query) {
                $query->latest()->take(5);
            }])
            ->latest()
            ->get();

        return Inertia::render('Bots/KnowledgeBase', [
            'knowledgeBases' => $knowledgeBases
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'language' => 'nullable|string|max:10',
            'visibility' => 'nullable|in:internal,bot,hybrid',
        ]);

        $tenantId = Auth::user()->tenant_id ?? Auth::user()->tenant->id ?? null;
        
        if (!$tenantId) {
            return back()->withErrors(['error' => 'No tenant associated with your account.']);
        }

        $knowledgeBase = KnowledgeBase::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'language' => $validated['language'] ?? 'en',
            'visibility' => $validated['visibility'] ?? 'bot',
        ]);

        return back()->with('success', 'Knowledge base created successfully.');
    }

    public function update(Request $request, KnowledgeBase $knowledgeBase)
    {
        if ($knowledgeBase->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'language' => 'nullable|string|max:10',
            'visibility' => 'nullable|in:internal,bot,hybrid',
        ]);

        $knowledgeBase->update($validated);

        return back()->with('success', 'Knowledge base updated successfully.');
    }

    public function destroy(KnowledgeBase $knowledgeBase)
    {
        if ($knowledgeBase->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $knowledgeBase->delete();

        return back()->with('success', 'Knowledge base deleted successfully.');
    }

    public function addSource(Request $request, KnowledgeBase $knowledgeBase)
    {
        $tenantId = Auth::user()->tenant_id ?? Auth::user()->tenant->id ?? null;
        
        if (!$tenantId || $knowledgeBase->tenant_id !== $tenantId) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|in:document,url,text,faq',
            'name' => 'required|string|max:255',
            'file' => 'nullable|required_if:type,document|file|mimes:pdf,txt,doc,docx|max:10240',
            'url' => 'nullable|required_if:type,url|url',
            'content' => 'nullable|required_if:type,text,faq|string',
        ]);

        $sourceConfig = [];

        // Handle file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('knowledge-sources', 'public');
            $sourceConfig['file_path'] = $path;
            $sourceConfig['file_name'] = $file->getClientOriginalName();
            $sourceConfig['file_size'] = $file->getSize();
            $sourceConfig['mime_type'] = $file->getMimeType();
        }

        // Handle URL
        if ($validated['type'] === 'url' && isset($validated['url'])) {
            $sourceConfig['url'] = $validated['url'];
        }

        // Handle text/FAQ content
        if (in_array($validated['type'], ['text', 'faq']) && isset($validated['content'])) {
            $sourceConfig['content'] = $validated['content'];
        }

        $source = $knowledgeBase->sources()->create([
            'tenant_id' => $tenantId,
            'type' => $validated['type'],
            'name' => $validated['name'],
            'source_config_json' => $sourceConfig,
            'is_active' => true,
        ]);

        // Dispatch background job to process the source
        \App\Jobs\ProcessKnowledgeSource::dispatch($source);

        return back()->with('success', 'Source added successfully. Processing in background...');
    }

    public function removeSource(KnowledgeSource $source)
    {
        if ($source->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        // Delete file if exists
        if ($source->type === 'document' && isset($source->source_config_json['file_path'])) {
            Storage::disk('public')->delete($source->source_config_json['file_path']);
        }

        $source->delete();

        return back()->with('success', 'Source removed successfully.');
    }

    public function syncSource(KnowledgeSource $source)
    {
        if ($source->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        // Re-dispatch processing job
        \App\Jobs\ProcessKnowledgeSource::dispatch($source);

        return back()->with('success', 'Source re-sync initiated.');
    }
}
