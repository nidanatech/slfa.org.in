<?php

namespace App\Http\Controllers;

use App\Models\GalleryAlbum;
use Inertia\Inertia;

class GalleryController extends Controller
{
    public function index()
    {
        return Inertia::render('SLFA/Gallery', [
            'albums' => GalleryAlbum::withCount('media')->latest()->get(),
        ]);
    }

    public function show(GalleryAlbum $album)
    {
        $album->load('media', 'event');

        return Inertia::render('SLFA/GalleryAlbumDetail', [
            'album' => $album,
        ]);
    }
}
