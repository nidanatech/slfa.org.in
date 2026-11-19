<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryAlbum;
use App\Models\GalleryMedia;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
        return Inertia::render('Admin/Gallery/Create', ['events' => Event::orderByDesc('start_at')->get(['id', 'title'])]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'event_id' => 'required|exists:events,id',
            'description' => 'nullable|string|max:5000',
            'date' => 'nullable|date',
        ]);

        GalleryAlbum::create($validated);

        return redirect()->route('admin.albums.index')->with('success', 'Album created.');
    }

    public function show(GalleryAlbum $album)
    {
        $album->load('media', 'event.programmeItems');
        return Inertia::render('Admin/Gallery/Show', ['album' => $album, 'events' => Event::orderByDesc('start_at')->get(['id', 'title'])]);
    }

    public function edit(GalleryAlbum $album)
    {
        $album->load('media', 'event.programmeItems');
        return Inertia::render('Admin/Gallery/Show', ['album' => $album, 'events' => Event::orderByDesc('start_at')->get(['id', 'title'])]);
    }

    public function update(Request $request, GalleryAlbum $album)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'event_id' => 'required|exists:events,id',
            'description' => 'nullable|string|max:5000',
            'date' => 'nullable|date',
        ]);

        if ((int) $album->event_id !== (int) $validated['event_id']) {
            $album->media()->update(['programme_item_id' => null]);
        }
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
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,webp,avif,mp4|max:20480',
            'programme_item_id' => ['nullable', Rule::exists('event_programme_items', 'id')->where('event_id', $album->event_id)],
            'caption' => 'nullable|string|max:255',
        ]);

        $path = $request->file('file')->store('gallery', 'public');
        $type = str_contains($request->file('file')->getMimeType(), 'video') ? 'video' : 'image';

        $album->media()->create([
            'path' => $path,
            'type' => $type,
            'programme_item_id' => $request->input('programme_item_id') ?: null,
            'caption' => $request->input('caption'),
        ]);

        if ($type === 'image' && !$album->cover_image_path) {
            $album->update(['cover_image_path' => $path]);
        }

        return back()->with('success', 'Media uploaded.');
    }

    public function updateMedia(Request $request, GalleryMedia $media)
    {
        $validated = $request->validate([
            'programme_item_id' => ['nullable', Rule::exists('event_programme_items', 'id')->where('event_id', $media->album->event_id)],
            'caption' => 'nullable|string|max:255',
        ]);
        $media->update($validated);

        return back()->with('success', 'Photo details updated.');
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
