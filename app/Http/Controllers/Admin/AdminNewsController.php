<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsPost;
use App\Models\NewsPhoto;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class AdminNewsController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/News/Index', [
            'posts' => NewsPost::orderByDesc('published_at')->orderByDesc('id')->paginate(20),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/News/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'category' => 'nullable|string',
            'published_at' => 'nullable|date',
            'cover_image' => 'nullable|image|mimes:jpeg,png,webp,avif|max:5120',
            'gallery_images' => 'nullable|array|max:12',
            'gallery_images.*' => 'image|mimes:jpeg,png,webp,avif|max:5120',
        ]);

        if ($request->hasFile('cover_image')) {
            $validated['cover_image_path'] = $request->file('cover_image')->store('news-covers', 'public');
        }
        unset($validated['cover_image']);
        unset($validated['gallery_images']);
        $validated['author_id'] = $request->user()->id;

        $news = NewsPost::create($validated);
        $this->addPhotos($request, $news);

        return redirect()->route('admin.news.index')->with('success', 'News post created.');
    }

    public function edit(NewsPost $news)
    {
        return Inertia::render('Admin/News/Edit', ['post' => $news->load('photos')]);
    }

    public function update(Request $request, NewsPost $news)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'category' => 'nullable|string',
            'published_at' => 'nullable|date',
            'cover_image' => 'nullable|image|mimes:jpeg,png,webp,avif|max:5120',
            'gallery_images' => 'nullable|array|max:12',
            'gallery_images.*' => 'image|mimes:jpeg,png,webp,avif|max:5120',
        ]);

        if ($request->hasFile('cover_image')) {
            $oldPath = $news->cover_image_path;
            $validated['cover_image_path'] = $request->file('cover_image')->store('news-covers', 'public');
            if ($oldPath && ! str_starts_with($oldPath, '/')) {
                Storage::disk('public')->delete($oldPath);
            }
        }
        unset($validated['cover_image']);
        unset($validated['gallery_images']);
        $news->update($validated);
        $this->addPhotos($request, $news);

        return redirect()->route('admin.news.index')->with('success', 'News post updated.');
    }

    public function destroy(NewsPost $news)
    {
        foreach ($news->photos as $photo) {
            Storage::disk('public')->delete($photo->path);
        }
        if ($news->cover_image_path && ! str_starts_with($news->cover_image_path, '/')) {
            Storage::disk('public')->delete($news->cover_image_path);
        }
        $news->delete();
        return redirect()->route('admin.news.index')->with('success', 'News post deleted.');
    }

    public function destroyPhoto(NewsPost $news, NewsPhoto $photo)
    {
        abort_unless($photo->news_post_id === $news->id, 404);
        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        if ($news->cover_image_path === $photo->path) {
            $news->update(['cover_image_path' => $news->photos()->first()?->path]);
        }

        return back()->with('success', 'Photo removed.');
    }

    private function addPhotos(Request $request, NewsPost $news): void
    {
        $position = (int) $news->photos()->max('sort_order') + 1;
        foreach ($request->file('gallery_images', []) as $file) {
            $path = $file->store('news-gallery', 'public');
            $news->photos()->create([
                'path' => $path,
                'sort_order' => $position++,
            ]);
            if (! $news->cover_image_path) {
                $news->update(['cover_image_path' => $path]);
            }
        }
    }
}
