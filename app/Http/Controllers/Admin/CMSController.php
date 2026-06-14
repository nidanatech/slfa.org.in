<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class CMSController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/CMS/Index', [
            'posts' => Post::with('author')->latest()->get()
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/CMS/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|string|in:blog,resource,documentation',
            'status' => 'required|string|in:draft,published,scheduled',
            'excerpt' => 'nullable|string',
            'category' => 'nullable|string',
        ]);

        $post = Post::create([
            ...$validated,
            'slug' => Str::slug($validated['title']),
            'author_id' => auth()->id(),
            'published_at' => $validated['status'] === 'published' ? now() : null,
        ]);

        return redirect()->route('admin.cms.index')->with('success', 'Post created successfully.');
    }

    public function edit(Post $post)
    {
        return Inertia::render('Admin/CMS/Create', [
            'post' => $post
        ]);
    }

    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|string|in:blog,resource,documentation',
            'status' => 'required|string|in:draft,published,scheduled',
            'excerpt' => 'nullable|string',
            'category' => 'nullable|string',
        ]);

        $post->update([
            ...$validated,
            'slug' => Str::slug($validated['title']),
            'published_at' => ($validated['status'] === 'published' && !$post->published_at) ? now() : $post->published_at,
        ]);

        return redirect()->route('admin.cms.index')->with('success', 'Post updated successfully.');
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return back()->with('success', 'Post deleted successfully.');
    }
}
