<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsPost;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminNewsController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/News/Index', [
            'posts' => NewsPost::latest()->paginate(20),
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
            // Cover image logic would go here
        ]);

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
        ]);

        $news->update($validated);

        return redirect()->route('admin.news.index')->with('success', 'News post updated.');
    }

    public function destroy(NewsPost $news)
    {
        $news->delete();
        return redirect()->route('admin.news.index')->with('success', 'News post deleted.');
    }
}
