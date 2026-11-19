<?php

namespace App\Http\Controllers;

use App\Models\GalleryAlbum;
use Inertia\Inertia;

class GalleryController extends Controller
{
    public function show(GalleryAlbum $album)
    {
        if ($album->event_id) {
            return redirect()->to(route('events.show', $album->event->public_id).'#photos', 301);
        }

        $album->load('media', 'event');

        return Inertia::render('SLFA/GalleryAlbumDetail', [
            'album' => $album,
        ]);
    }
}
