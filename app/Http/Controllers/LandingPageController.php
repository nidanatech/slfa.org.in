<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\BrandVoice;
use App\Models\DesignKit;
use App\Services\LandingPageGeneratorService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class LandingPageController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $landingPages = LandingPage::where('tenant_id', $tenantId)->latest()->get();
        
        $stats = [
            'total' => $landingPages->count(),
            'published' => $landingPages->where('is_published', true)->count(),
            'drafts' => $landingPages->where('is_published', false)->count(),
            'total_views' => $landingPages->sum('views_count') ?? 0,
        ];

        return Inertia::render('LandingPages/Index', [
            'landingPages' => $landingPages,
            'stats' => $stats
        ]);
    }

    public function create()
    {
        return Inertia::render('LandingPages/CreateWizard');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:landing_pages,slug',
            'content' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        $landingPage = LandingPage::create([
            'user_id' => auth()->id(),
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'content' => $validated['content'] ?? [],
            'settings' => $validated['settings'] ?? [],
        ]);

        return redirect()->route('landing-pages.edit', $landingPage->id)
            ->with('success', 'Landing page created successfully.');
    }

    public function edit(LandingPage $landingPage)
    {
        return Inertia::render('LandingPages/Builder/LandingPageBuilder', [
            'landingPage' => $landingPage
        ]);
    }

    public function update(Request $request, LandingPage $landingPage)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:landing_pages,slug,' . $landingPage->id,
            'content' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        $landingPage->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'content' => $validated['content'],
            'settings' => $validated['settings'],
        ]);

        return back()->with('success', 'Landing page updated successfully.');
    }

    public function togglePublish(LandingPage $landingPage)
    {
        $landingPage->update([
            'is_published' => !$landingPage->is_published,
            'published_at' => !$landingPage->is_published ? now() : null,
        ]);

        return back()->with('success', $landingPage->is_published ? 'Landing page published!' : 'Landing page unpublished.');
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120', // 5MB limit
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('landing-pages/images', 'public');
            return response()->json([
                'url' => Storage::url($path),
                'path' => $path
            ]);
        }

        return response()->json(['error' => 'No image uploaded'], 400);
    }

    public function destroy(LandingPage $landingPage)
    {
        $landingPage->delete();
        return redirect()->route('landing-pages.index')->with('success', 'Landing page deleted.');
    }

    /**
     * Generate a landing page using AI
     */
    public function generate(Request $request, LandingPageGeneratorService $generator)
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:1000',
            'brand_voice_id' => 'nullable|exists:brand_voices,id',
            'design_kit_id' => 'nullable|exists:design_kits,id',
        ]);

        try {
            $result = $generator->generateFromPrompt(
                $validated['prompt'],
                auth()->user()->tenant_id,
                $validated['brand_voice_id'] ?? null,
                $validated['design_kit_id'] ?? null
            );

            // Generate a slug from the prompt
            $slug = Str::slug(Str::limit($validated['prompt'], 50));
            $originalSlug = $slug;
            $counter = 1;
            while (LandingPage::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            $landingPage = LandingPage::create([
                'user_id' => auth()->id(),
                'tenant_id' => auth()->user()->tenant_id,
                'name' => Str::limit($validated['prompt'], 100),
                'slug' => $slug,
                'content' => $result['content'],
                'settings' => $result['theme'],
                'generation_prompt' => $result['generation_prompt'],
                'brand_context' => $result['brand_context'],
                'language' => 'en',
            ]);

            return response()->json([
                'success' => true,
                'landing_page' => $landingPage,
                'redirect' => route('landing-pages.edit', $landingPage->id)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Regenerate a specific section with AI
     */
    public function regenerateSection(Request $request, LandingPage $landingPage, LandingPageGeneratorService $generator)
    {
        $validated = $request->validate([
            'section_index' => 'required|integer',
            'instruction' => 'required|string|max:500',
        ]);

        try {
            $content = $landingPage->content;
            $sectionIndex = $validated['section_index'];

            if (!isset($content[$sectionIndex])) {
                return response()->json(['error' => 'Section not found'], 404);
            }

            $brandVoiceId = $landingPage->brand_context['brand_voice_id'] ?? null;
            $regenerated = $generator->regenerateSection(
                $content[$sectionIndex],
                $validated['instruction'],
                $brandVoiceId
            );

            $content[$sectionIndex] = $regenerated;
            $landingPage->update(['content' => $content]);

            return response()->json([
                'success' => true,
                'section' => $regenerated
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize page for SEO
     */
    public function optimizeSEO(Request $request, LandingPage $landingPage, LandingPageGeneratorService $generator)
    {
        $validated = $request->validate([
            'keywords' => 'required|string|max:200',
        ]);

        try {
            $optimized = $generator->optimizeForSEO(
                $landingPage->content,
                $validated['keywords']
            );

            $landingPage->update([
                'content' => $optimized,
                'seo_metadata' => [
                    'keywords' => $validated['keywords'],
                    'optimized_at' => now()->toISOString(),
                ]
            ]);

            return response()->json([
                'success' => true,
                'content' => $optimized
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate FAQs for the page
     */
    public function generateFAQs(LandingPage $landingPage, LandingPageGeneratorService $generator)
    {
        try {
            $brandVoiceId = $landingPage->brand_context['brand_voice_id'] ?? null;
            $faqSection = $generator->generateFAQs($landingPage->content, $brandVoiceId);

            $content = $landingPage->content;
            $content[] = $faqSection;
            $landingPage->update(['content' => $content]);

            return response()->json([
                'success' => true,
                'section' => $faqSection
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Translate page to another language
     */
    public function translate(Request $request, LandingPage $landingPage, LandingPageGeneratorService $generator)
    {
        $validated = $request->validate([
            'target_language' => 'required|string|max:50',
        ]);

        try {
            $translated = $generator->translatePage(
                $landingPage->content,
                $validated['target_language']
            );

            // Create a new landing page for the translation
            $newPage = $landingPage->replicate();
            $newPage->slug = $landingPage->slug . '-' . strtolower($validated['target_language']);
            $newPage->name = $landingPage->name . ' (' . $validated['target_language'] . ')';
            $newPage->content = $translated;
            $newPage->language = $validated['target_language'];
            $newPage->is_published = false;
            $newPage->save();

            return response()->json([
                'success' => true,
                'landing_page' => $newPage,
                'redirect' => route('landing-pages.edit', $newPage->id)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get brand voices and design kits for AI generation
     */
    public function getBrandData()
    {
        $brandVoices = BrandVoice::where('tenant_id', auth()->user()->tenant_id)
            ->active()
            ->get(['id', 'brand_name', 'business_type']);

        $designKits = DesignKit::where('tenant_id', auth()->user()->tenant_id)
            ->active()
            ->get(['id', 'name', 'colors']);

        return response()->json([
            'brandVoices' => $brandVoices,
            'designKits' => $designKits,
        ]);
    }

    /**
     * Get available channel lead capture forms
     */
    public function getChannelForms()
    {
        $forms = \App\Models\Channel::where('tenant_id', auth()->user()->tenant_id)
            ->where('type', 'lead_form')
            ->where('is_active', true)
            ->get(['id', 'name', 'config']);

        return response()->json(['forms' => $forms]);
    }

    /**
     * Get available AI bots for web widget
     */
    public function getAiBots()
    {
        $bots = \App\Models\AiBot::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->get(['id', 'name', 'description']);

        return response()->json(['bots' => $bots]);
    }

    /**
     * Track page view for analytics
     */
    public function trackView(LandingPage $landingPage)
    {
        // Increment view count
        $landingPage->increment('views_count');
        
        return response()->json(['success' => true]);
    }

    /**
     * Track click for analytics
     */
    public function trackClick(LandingPage $landingPage, Request $request)
    {
        $validated = $request->validate([
            'element' => 'required|string',
            'url' => 'nullable|string'
        ]);

        \Log::info('Landing page click', [
            'landing_page_id' => $landingPage->id,
            'element' => $validated['element'],
            'url' => $validated['url'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Show public landing page
     */
    public function showPublic($slug)
    {
        $landingPage = LandingPage::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return Inertia::render('LandingPages/PublicRenderer', [
            'landingPage' => $landingPage
        ]);
    }

    /**
     * Handle form submission and create contact
     */
    public function submitForm(Request $request, $slug)
    {
        $landingPage = LandingPage::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $validated = $request->validate([
            'form_data' => 'required|array'
        ]);

        // Increment submissions count
        $landingPage->increment('submissions_count');

        // Create or update contact
        $formData = $validated['form_data'];
        $email = $formData['Email'] ?? $formData['email'] ?? null;
        $name = $formData['Name'] ?? $formData['name'] ?? $formData['Full Name'] ?? null;
        $phone = $formData['Phone'] ?? $formData['phone'] ?? null;

        // Determine which channel to use (from form block or landing page)
        $channelId = $request->input('channel_id') ?? $landingPage->channel_id;

        if ($email) {
            $contact = \App\Models\Contact::updateOrCreate(
                [
                    'email' => $email,
                    'tenant_id' => $landingPage->tenant_id
                ],
                [
                    'name' => $name ?? 'Landing Page Lead',
                    'phone' => $phone,
                    'source' => 'landing_page',
                    'channel_id' => $channelId, // Link to channel if specified
                    'metadata' => [
                        'landing_page_id' => $landingPage->id,
                        'landing_page_name' => $landingPage->name,
                        'form_data' => $formData,
                        'submitted_at' => now()->toDateTimeString()
                    ]
                ]
            );

            // Create submission record
            \App\Models\LandingPageSubmission::create([
                'landing_page_id' => $landingPage->id,
                'contact_id' => $contact->id,
                'form_data' => $formData,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
        }

        return back()->with('success', 'Form submitted successfully!');
    }
}
