<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryAlbum;
use App\Models\GalleryMedia;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class AdminGalleryController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Gallery/Index', [
            'albums' => GalleryAlbum::withCount('media')->latest()->paginate(20),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Gallery/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'event_id' => 'nullable|exists:events,id',
            'description' => 'nullable|string|max:5000',
            'date' => 'nullable|date',
        ]);

        GalleryAlbum::create($validated);

        return redirect()->route('admin.albums.index')->with('success', 'Album created.');
    }

    public function show(GalleryAlbum $album)
    {
        $album->load('media', 'event');
        return Inertia::render('Admin/Gallery/Show', ['album' => $album]);
    }

    public function edit(GalleryAlbum $album)
    {
        $album->load('media', 'event');
        return Inertia::render('Admin/Gallery/Show', ['album' => $album]);
    }

    public function update(Request $request, GalleryAlbum $album)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'event_id' => 'nullable|exists:events,id',
            'description' => 'nullable|string|max:5000',
            'date' => 'nullable|date',
        ]);

        $album->update($validated);

        return redirect()->route('admin.albums.index')->with('success', 'Album updated.');
    }

    public function destroy(GalleryAlbum $album)
    {
        $album->delete();
        return redirect()->route('admin.albums.index')->with('success', 'Album deleted.');
    }

    public function uploadMedia(Request $request, GalleryAlbum $album)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,mp4|max:20480',
        ]);

        $path = $request->file('file')->store('gallery', 'public');
        $type = str_contains($request->file('file')->getMimeType(), 'video') ? 'video' : 'image';

        $album->media()->create([
            'path' => $path,
            'type' => $type,
        ]);

        if ($type === 'image' && !$album->cover_image_path) {
            $album->update(['cover_image_path' => $path]);
        }

        return back()->with('success', 'Media uploaded.');
    }

    public function destroyMedia(GalleryMedia $media)
    {
        if (Storage::disk('public')->exists($media->path)) {
            Storage::disk('public')->delete($media->path);
        }
        $media->delete();
        
        return back()->with('success', 'Media deleted.');
    }
}
