<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Inertia\Inertia;

class BlogController extends Controller
{
    public function index()
    {
        return Inertia::render('Website/Blog/Index', [
            'posts' => Post::where('type', Post::TYPE_BLOG)
                ->published()
                ->latest('published_at')
                ->paginate(9),
            'categories' => [], // Placeholder until we unify categories
            'canLogin' => \Illuminate\Support\Facades\Route::has('login'),
            'canRegister' => \Illuminate\Support\Facades\Route::has('register'),
        ]);
    }

    public function show($slug)
    {
        $post = Post::where('type', Post::TYPE_BLOG)
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedPosts = Post::where('type', Post::TYPE_BLOG)
            ->where('id', '!=', $post->id)
            ->published()
            ->limit(3)
            ->get();

        return Inertia::render('Website/Blog/Show', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
            'canLogin' => \Illuminate\Support\Facades\Route::has('login'),
            'canRegister' => \Illuminate\Support\Facades\Route::has('register'),
        ]);
    }
}
