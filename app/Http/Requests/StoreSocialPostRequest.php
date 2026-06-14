<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\SocialPlannerSettings;

class StoreSocialPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $settings = SocialPlannerSettings::getOrCreateForTenant(auth()->user()->tenant_id);

        return [
            'content' => "required|string|max:{$settings->max_content_length}",
            'hashtags' => 'nullable|array|max:30',
            'hashtags.*' => 'string|max:50',
            'mentions' => 'nullable|array',
            'mentions.*' => 'string|max:100',
            'brand_voice_id' => 'nullable|exists:brand_voices,id',
            'design_kit_id' => 'nullable|exists:design_kits,id',
            'selected_channels' => 'required|array|min:1',
            'selected_channels.*' => 'exists:channels,id',
            'media' => 'nullable|array|max:10',
            'media.*' => 'file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:51200', // 50MB
            'scheduled_for' => 'nullable|date|after:now',
            'ai_generated' => 'boolean',
            'ai_prompt' => 'nullable|string',
            'gmb_options' => 'nullable|array',
            'gmb_options.cta_type' => 'nullable|string|in:LEARN_MORE,BOOK,ORDER,SHOP,SIGN_UP,CALL',
            'gmb_options.cta_url' => 'nullable|url',
            'gmb_options.topic_type' => 'nullable|string|in:STANDARD,EVENT,OFFER,ALERT',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Post content is required.',
            'content.max' => 'Post content exceeds maximum length.',
            'selected_channels.required' => 'Please select at least one channel.',
            'selected_channels.min' => 'Please select at least one channel.',
            'media.*.max' => 'Each media file must not exceed 50MB.',
            'scheduled_for.after' => 'Scheduled time must be in the future.',
        ];
    }
}
