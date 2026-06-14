<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\LandingPageSubmission;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicLandingPageController extends Controller
{
    public function show($slug)
    {
        $landingPage = LandingPage::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return Inertia::render('LandingPages/PublicRenderer', [
            'landingPage' => $landingPage
        ]);
    }

    public function submit(Request $request, $slug)
    {
        $landingPage = LandingPage::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        // Basic validation for form data
        $validated = $request->validate([
            'form_data' => 'required|array',
        ]);

        LandingPageSubmission::create([
            'landing_page_id' => $landingPage->id,
            'form_data' => $validated['form_data'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Form submitted successfully!');
    }
}
