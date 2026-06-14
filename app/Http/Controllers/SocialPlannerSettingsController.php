<?php

namespace App\Http\Controllers;

use App\Models\SocialPlannerSettings;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SocialPlannerSettingsController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            // Image Generation
            'image_model' => 'required|string|in:dall-e-3,dall-e-2,midjourney,stable-diffusion,imagen',
            'image_model_api_key' => 'nullable|string',
            'image_quality' => 'required|string|in:standard,hd',
            // Approval Workflow
            'requires_approval' => 'boolean',
            'approver_id' => 'nullable|exists:users,id',
            'notify_approver_email' => 'boolean',
            // Posting Settings
            'auto_publish_approved' => 'boolean',
            'default_post_delay_minutes' => 'integer|min:0',
            // Content Settings
            'max_content_length' => 'integer|min:100|max:5000',
            'auto_hashtag_suggestions' => 'boolean',
            'auto_emoji_suggestions' => 'boolean',
            // Analytics
            'track_engagement' => 'boolean',
            'track_clicks' => 'boolean',
            'utm_source' => 'required|string',
            'utm_medium' => 'required|string',
            // AI Settings
            'ai_model' => 'required|string|in:gpt-4,gpt-3.5-turbo,claude,gemini-nano,gemini-pro',
            'ai_api_key' => 'nullable|string',
            'ai_max_tokens' => 'integer|min:100|max:2000',
            'ai_temperature' => 'numeric|min:0|max:1',
        ]);

        $settings = SocialPlannerSettings::getOrCreateForTenant(auth()->user()->tenant_id);
        $settings->update($validated);

        return redirect()->back()->with('success', 'Social Planner settings updated successfully!');
    }
}
