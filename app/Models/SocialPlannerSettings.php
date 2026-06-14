<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;

class SocialPlannerSettings extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        // Image Generation
        'image_model',
        'image_model_api_key',
        'image_quality',
        'image_size',
        // Approval Workflow
        'requires_approval',
        'approver_id',
        'notify_approver_email',
        'notify_approver_slack',
        'slack_webhook_url',
        // Posting Settings
        'auto_publish_approved',
        'default_post_delay_minutes',
        'default_timezone',
        // Content Settings
        'max_content_length',
        'auto_hashtag_suggestions',
        'auto_emoji_suggestions',
        'prohibited_words',
        // Analytics & Tracking
        'track_engagement',
        'track_clicks',
        'utm_source',
        'utm_medium',
        // AI Settings
        'ai_model',
        'ai_api_key',
        'ai_max_tokens',
        'ai_temperature',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'notify_approver_email' => 'boolean',
        'notify_approver_slack' => 'boolean',
        'auto_publish_approved' => 'boolean',
        'auto_hashtag_suggestions' => 'boolean',
        'auto_emoji_suggestions' => 'boolean',
        'track_engagement' => 'boolean',
        'track_clicks' => 'boolean',
        'prohibited_words' => 'array',
        'default_post_delay_minutes' => 'integer',
        'max_content_length' => 'integer',
        'ai_max_tokens' => 'integer',
        'ai_temperature' => 'float',
    ];

    protected $hidden = [
        'image_model_api_key',
        'ai_api_key',
    ];

    // Relationships
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    // Helper Methods
    public static function getOrCreateForTenant($tenantId)
    {
        return static::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'image_model' => 'dall-e-3',
                'image_quality' => 'standard',
                'image_size' => '1024x1024',
                'requires_approval' => false,
                'auto_publish_approved' => true,
                'default_timezone' => 'UTC',
                'max_content_length' => 2000,
                'auto_hashtag_suggestions' => true,
                'auto_emoji_suggestions' => true,
                'track_engagement' => true,
                'track_clicks' => true,
                'utm_source' => 'social_planner',
                'utm_medium' => 'social',
                'ai_model' => 'gpt-4',
                'ai_max_tokens' => 500,
                'ai_temperature' => 0.7,
            ]
        );
    }
}
