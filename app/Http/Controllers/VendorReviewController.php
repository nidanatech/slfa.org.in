<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorReview;
use App\Models\VendorReviewScore;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VendorReviewController extends Controller
{
    public function index(Vendor $vendor)
    {
        $reviews = $vendor->reviews()
            ->with(['user', 'scores.criteria'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Vendors/Reviews/Index', [
            'vendor' => $vendor,
            'reviews' => $reviews,
        ]);
    }

    public function store(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'review_text' => 'required|string|min:10',
            'scores' => 'required|array',
            'scores.*.criteria_id' => 'required|exists:vendor_rating_criteria,id',
            'scores.*.score' => 'required|integer|min:1|max:5',
            'is_verified' => 'boolean',
        ]);

        // Analyze Sentiment (using AI logic directly here for simplicity and to avoid cross-controller dependency issues)
        $sentimentScore = 0;
        $sentimentLabel = 'neutral';
        
        try {
            $response = app(\App\Http\Controllers\Api\AIController::class)->analyzeVendorSentiment(new Request([
                'review_text' => $validated['review_text']
            ]));
            
            if ($response->getData()->success) {
                $sentimentScore = $response->getData()->sentiment_score;
                $sentimentLabel = $response->getData()->sentiment_label;
            }
        } catch (\Exception $e) {
            // Silently fail AI sentiment if it crashes, default to neutral
        }

        $review = $vendor->reviews()->create([
            'user_id' => auth()->id(),
            'rating' => $validated['rating'],
            'review_text' => $validated['review_text'],
            'sentiment_score' => $sentimentScore,
            'sentiment_label' => $sentimentLabel,
            'is_verified' => $validated['is_verified'] ?? false,
        ]);

        foreach ($validated['scores'] as $scoreData) {
            $review->scores()->create([
                'criteria_id' => $scoreData['criteria_id'],
                'score' => $scoreData['score'],
            ]);
        }

        // Recalculate Vendor Overall Score
        // Simple average of all review ratings for now (or could be weighted based on criteria)
        $newScore = $vendor->reviews()->avg('rating');
        
        // Determine Risk Level
        $riskLevel = 'low';
        if ($newScore < 2) $riskLevel = 'critical';
        elseif ($newScore < 3) $riskLevel = 'high';
        elseif ($newScore < 4) $riskLevel = 'medium';

        $vendor->update([
            'overall_score' => $newScore,
            'risk_level' => $riskLevel,
        ]);

        return back()->with('success', 'Review submitted successfully.');
    }
}
