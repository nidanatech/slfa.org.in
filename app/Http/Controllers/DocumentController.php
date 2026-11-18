<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DocumentController extends Controller
{
    public function index()
    {
        return Inertia::render('SLFA/Documents', [
            'documents' => Document::latest('uploaded_at')->get()->groupBy('category'),
        ]);
    }

    public function download(Document $document)
    {
        $disk = Storage::disk('local')->exists($document->file_path) ? 'local' : 'public';
        if (!Storage::disk($disk)->exists($document->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk($disk)->download($document->file_path, $document->title);
    }
}
