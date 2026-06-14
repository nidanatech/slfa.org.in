<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class ResourceController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::where('type', Post::TYPE_RESOURCE)->published();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $posts = $query->latest()->get();
        
        // Group by 'category' string and transform to format expected by view
        $categories = $posts->groupBy('category')->map(function ($items, $categoryName) {
            return [
                'id' => Str::slug($categoryName),
                'name' => $categoryName,
                'help_articles' => $items->values() // In view it's help_articles
            ];
        })->values();
            
        return Inertia::render('Website/Resources/Index', [
            'categories' => $categories,
            'canLogin' => \Illuminate\Support\Facades\Route::has('login'),
            'canRegister' => \Illuminate\Support\Facades\Route::has('register'),
        ]);
    }

    public function show($slug)
    {
        $article = Post::where('type', Post::TYPE_RESOURCE)
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        // Get all resource categories and posts for sidebar
        $allPosts = Post::where('type', Post::TYPE_RESOURCE)->published()->get();
        $categories = $allPosts->groupBy('category')->map(function ($items, $categoryName) {
            return [
                'id' => Str::slug($categoryName),
                'name' => $categoryName,
                'help_articles' => $items->values()
            ];
        })->values();

        return Inertia::render('Website/Resources/Show', [
            'article' => $article,
            'categories' => $categories,
            'canLogin' => \Illuminate\Support\Facades\Route::has('login'),
            'canRegister' => \Illuminate\Support\Facades\Route::has('register'),
        ]);
    }
}
