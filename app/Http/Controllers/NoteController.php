<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class NoteController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'object_type' => 'required|string',
            'object_id' => 'required|integer',
            'content' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240', // 10MB
            'visibility' => 'required|string|in:internal,external',
        ]);

        $note = Note::create([
            'tenant_id' => Auth::user()->tenant_id,
            'object_type' => $validated['object_type'],
            'object_id' => $validated['object_id'],
            'author_id' => Auth::id(),
            'content' => $validated['content'],
            'visibility' => $validated['visibility'],
            'source' => 'manual',
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('notes', 'public');
                $note->attachments()->create([
                    'file_url' => '/storage/' . $path,
                    'file_type' => $file->getMimeType(),
                ]);
            }
        }

        // Load the note with author for returning
        $note->load('author');

        return back()->with('success', 'Note added successfully.');
    }
}
