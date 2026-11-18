<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class AdminDocumentController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Documents/Index', [
            'documents' => Document::latest('uploaded_at')->paginate(20),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Documents/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|in:bylaws,minutes,registration,other',
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $path = $request->file('file')->store('documents', 'local');
        abort_unless($path, 500, 'Could not store document.');

        Document::create([
            'title' => $request->title,
            'category' => $request->category,
            'file_path' => $path,
            'uploaded_at' => now(),
        ]);

        return redirect()->route('admin.documents.index')->with('success', 'Document uploaded.');
    }

    public function destroy(Document $document)
    {
        foreach (['local', 'public'] as $disk) {
            Storage::disk($disk)->delete($document->file_path);
        }
        $document->delete();

        return redirect()->route('admin.documents.index')->with('success', 'Document deleted.');
    }
}
