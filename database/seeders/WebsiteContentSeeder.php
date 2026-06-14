<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\HelpArticle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WebsiteContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Blog Categories
        $blogCategories = [
            'Marketing Insights',
            'Sales Strategies',
            'Business Growth',
            'Product Updates'
        ];

        foreach ($blogCategories as $cat) {
            Category::firstOrCreate([
                'slug' => Str::slug($cat),
                'type' => 'blog'
            ], [
                'name' => $cat,
                'description' => "Latest insights about $cat"
            ]);
        }

        // Resource Categories
        $resourceCategories = [
            'Getting Started',
            'Account Management',
            'Marketing Activities',
            'Sales Activities'
        ];

        foreach ($resourceCategories as $cat) {
            Category::firstOrCreate([
                'slug' => Str::slug($cat),
                'type' => 'resource'
            ], [
                'name' => $cat,
                'description' => "Help articles for $cat"
            ]);
        }

        // Seed Blog Posts
        $marketingCat = Category::where('slug', 'marketing-insights')->first();
        if ($marketingCat) {
            BlogPost::feature()->create([
                'category_id' => $marketingCat->id,
                'title' => 'Revolutionizing Your Marketing Activities with AI',
                'slug' => 'revolutionizing-marketing-activities-ai',
                'excerpt' => 'Discover how AI can automate your marketing workflows and boost engagement.',
                'content' => file_get_contents(__DIR__ . '/content/blog_marketing_ai.md') ?: 'Placeholder content...',
                'published_at' => now(),
                'is_featured' => true,
                'image_url' => '/images/blog/marketing-ai.jpg' // Placeholder
            ]);
            
            // Create dummy content if file doesn't exist to prevent errors
            for ($i = 1; $i <= 5; $i++) {
                BlogPost::create([
                    'category_id' => $marketingCat->id,
                    'title' => "Marketing Activity Strategy #$i",
                    'slug' => "marketing-activity-strategy-$i",
                    'excerpt' => "Learn about strategy #$i to improve your outcome.",
                    'content' => "## Strategy #$i\n\nThis is a detailed explanation of how to leverage this strategy for your marketing activities.",
                    'published_at' => now()->subDays($i),
                    'author_name' => 'Sarah Connor'
                ]);
            }
        }

        // Seed Help Articles
        $gettingStarted = Category::where('slug', 'getting-started')->first();
        if ($gettingStarted) {
             HelpArticle::create([
                'category_id' => $gettingStarted->id,
                'title' => 'How to Create Your First Activity',
                'slug' => 'how-to-create-first-activity',
                'content' => "## Creating an Activity\n\n1. Go to Dashboard.\n2. Click 'New Activity'.\n3. Select type (Marketing/Sales).\n4. Save.",
                'is_featured' => true
            ]);
        }
    }
}
