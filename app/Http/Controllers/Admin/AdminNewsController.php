<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsPost;
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
        ]);

        if ($request->hasFile('cover_image')) {
            $validated['cover_image_path'] = $request->file('cover_image')->store('news-covers', 'public');
        }
        unset($validated['cover_image']);
        $validated['author_id'] = $request->user()->id;

        NewsPost::create($validated);

        return redirect()->route('admin.news.index')->with('success', 'News post created.');
    }

    public function edit(NewsPost $news)
    {
        return Inertia::render('Admin/News/Edit', ['post' => $news]);
    }

    public function update(Request $request, NewsPost $news)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'category' => 'nullable|string',
            'published_at' => 'nullable|date',
            'cover_image' => 'nullable|image|mimes:jpeg,png,webp,avif|max:5120',
        ]);

        if ($request->hasFile('cover_image')) {
            $oldPath = $news->cover_image_path;
            $validated['cover_image_path'] = $request->file('cover_image')->store('news-covers', 'public');
            if ($oldPath && ! str_starts_with($oldPath, '/')) {
                Storage::disk('public')->delete($oldPath);
            }
        }
        unset($validated['cover_image']);
        $news->update($validated);

        return redirect()->route('admin.news.index')->with('success', 'News post updated.');
    }

    public function destroy(NewsPost $news)
    {
        $news->delete();
        return redirect()->route('admin.news.index')->with('success', 'News post deleted.');
    }
}
