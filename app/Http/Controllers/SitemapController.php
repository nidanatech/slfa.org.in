<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

class SitemapController extends Controller
{
    public function index()
    {
        $urls = [
            ['loc' => url('/'), 'lastmod' => now()->toAtomString(), 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => url('/activities'), 'lastmod' => now()->toAtomString(), 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['loc' => url('/pricing'), 'lastmod' => now()->toAtomString(), 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['loc' => url('/blog'), 'lastmod' => now()->toAtomString(), 'changefreq' => 'daily', 'priority' => '0.8'],
            ['loc' => url('/resources'), 'lastmod' => now()->toAtomString(), 'changefreq' => 'weekly', 'priority' => '0.7'],
            ['loc' => url('/docs'), 'lastmod' => now()->toAtomString(), 'changefreq' => 'weekly', 'priority' => '0.7'],
            ['loc' => url('/partner'), 'lastmod' => now()->toAtomString(), 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => url('/privacy'), 'lastmod' => now()->toAtomString(), 'changefreq' => 'monthly', 'priority' => '0.3'],
            ['loc' => url('/terms'), 'lastmod' => now()->toAtomString(), 'changefreq' => 'monthly', 'priority' => '0.3'],
        ];

        // Add dynamic activities
        $activities = ['marketing', 'sales', 'customer-support', 'lead-capture', 'content-creation', 'business-intelligence'];
        foreach ($activities as $slug) {
            $urls[] = [
                'loc' => route('activities.' . $slug),
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8'
            ];
        }

        $xml = view('sitemap', compact('urls'))->render();

        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
