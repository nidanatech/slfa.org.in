<?php

namespace App\Http\Controllers;

use App\Models\SocialPost;
use App\Models\Channel;
use App\Models\BrandVoice;
use App\Models\DesignKit;
use App\Models\SocialPlannerSettings;
use App\Http\Requests\StoreSocialPostRequest;
use App\Jobs\PublishSocialPostJob;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SocialPlannerController extends Controller
{
    public function index()
    {
        $posts = SocialPost::forTenant(auth()->user()->tenant_id)
            ->with(['user', 'channels', 'media', 'brandVoice', 'designKit', 'approver'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Marketing/SocialPlanner/Index', [
            'posts' => $posts
        ]);
    }

    public function create()
    {
        // Get connected channels
        $tenant = auth()->user()->tenant;
        $channels = Channel::forTenant($tenant->id)
            ->where('is_active', true)
            ->get()
            ->filter(function ($channel) use ($tenant) {
                return $tenant->isChannelEnabled($channel->type);
            })
            ->map(function ($channel) {
                return [
                    'id' => $channel->id,
                    'type' => $channel->type,
                    'name' => $channel->name,
                    'is_active' => $channel->is_active,
                ];
            })->values();

        // Get brand voices
        $brandVoices = BrandVoice::forTenant(auth()->user()->tenant_id)
            ->active()
            ->get()
            ->map(function ($voice) {
                return [
                    'id' => $voice->id,
                    'brand_name' => $voice->brand_name,
                    'business_type' => $voice->business_type,
                ];
            });

        // Get design kits
        $designKits = DesignKit::forTenant(auth()->user()->tenant_id)
            ->active()
            ->get()
            ->map(function ($kit) {
                return [
                    'id' => $kit->id,
                    'name' => $kit->name,
                    'colors' => $kit->colors ?? [],
                ];
            });

        return Inertia::render('Marketing/SocialPlanner/CreatePost', [
            'channels' => $channels,
            'brandVoices' => $brandVoices,
            'designKits' => $designKits,
        ]);
    }

    public function store(StoreSocialPostRequest $request)
    {
        $post = SocialPost::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'content' => $request->content,
            'hashtags' => $request->hashtags,
            'mentions' => $request->mentions,
            'brand_voice_id' => $request->brand_voice_id,
            'design_kit_id' => $request->design_kit_id,
            'scheduled_for' => $request->scheduled_for,
            'status' => $request->scheduled_for ? 'scheduled' : 'draft',
            'ai_generated' => $request->ai_generated ?? false,
            'ai_prompt' => $request->ai_prompt,
            'metadata' => $request->has('gmb_options') ? [
                'call_to_action' => [
                    'type' => $request->input('gmb_options.cta_type'),
                    'url' => $request->input('gmb_options.cta_url'),
                ],
                'topic_type' => $request->input('gmb_options.topic_type'),
            ] : null,
        ]);

        // Attach channels
        if ($request->selected_channels) {
            foreach ($request->selected_channels as $channelId) {
                $post->postChannels()->create([
                    'channel_id' => $channelId,
                    'status' => 'pending',
                ]);
            }
        }

        // Handle media uploads
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $index => $file) {
                $path = $file->store('social-posts/media', 'public');
                
                // Get image dimensions if it's an image
                $width = null;
                $height = null;
                if (str_starts_with($file->getMimeType(), 'image/')) {
                    [$width, $height] = getimagesize($file->getRealPath());
                }

                $post->media()->create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'file_path' => $path,
                    'file_type' => str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'video',
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'original_filename' => $file->getClientOriginalName(),
                    'width' => $width,
                    'height' => $height,
                    'sort_order' => $index,
                ]);
            }
        }

        // Check if approval required
        $settings = SocialPlannerSettings::getOrCreateForTenant(auth()->user()->tenant_id);
        if ($settings->requires_approval) {
            $post->update(['approval_status' => 'pending']);
            
            // Create approval request record
            $post->approvals()->create([
                'user_id' => auth()->id(),
                'tenant_id' => auth()->user()->tenant_id,
                'action' => 'requested',
                'previous_status' => 'draft',
                'new_status' => 'pending_approval',
            ]);

            // TODO: Send notification to approver
            if ($settings->approver_id && $settings->notify_approver_email) {
                // Dispatch notification email
            }
        }

        return redirect()->route('marketing.social-planner.index')
            ->with('success', 'Post created successfully!');
    }

    public function edit($id)
    {
        $post = SocialPost::forTenant(auth()->user()->tenant_id)
            ->with(['channels', 'media', 'brandVoice', 'designKit'])
            ->findOrFail($id);

        // Get available channels, brand voices, design kits
        $tenant = auth()->user()->tenant;
        $channels = Channel::forTenant($tenant->id)
            ->where('is_active', true)
            ->get()
            ->filter(function ($channel) use ($tenant) {
                return $tenant->isChannelEnabled($channel->type);
            })->values();


        $brandVoices = BrandVoice::forTenant(auth()->user()->tenant_id)->active()->get();
        $designKits = DesignKit::forTenant(auth()->user()->tenant_id)->active()->get();

        return Inertia::render('Marketing/SocialPlanner/EditPost', [
            'post' => $post,
            'channels' => $channels,
            'brandVoices' => $brandVoices,
            'designKits' => $designKits,
        ]);
    }

    public function update(StoreSocialPostRequest $request, $id)
    {
        $post = SocialPost::forTenant(auth()->user()->tenant_id)->findOrFail($id);

        $post->update([
            'content' => $request->content,
            'hashtags' => $request->hashtags,
            'mentions' => $request->mentions,
            'brand_voice_id' => $request->brand_voice_id,
            'design_kit_id' => $request->design_kit_id,
            'scheduled_for' => $request->scheduled_for,
            'metadata' => $request->has('gmb_options') ? [
                'call_to_action' => [
                    'type' => $request->input('gmb_options.cta_type'),
                    'url' => $request->input('gmb_options.cta_url'),
                ],
                'topic_type' => $request->input('gmb_options.topic_type'),
            ] : $post->metadata,
        ]);

        // Update channels
        if ($request->selected_channels) {
            $post->postChannels()->delete();
            foreach ($request->selected_channels as $channelId) {
                $post->postChannels()->create([
                    'channel_id' => $channelId,
                    'status' => 'pending',
                ]);
            }
        }

        return redirect()->route('marketing.social-planner.index')
            ->with('success', 'Post updated successfully!');
    }

    public function destroy($id)
    {
        $post = SocialPost::forTenant(auth()->user()->tenant_id)->findOrFail($id);
        
        // Delete associated media files
        foreach ($post->media as $media) {
            $media->delete(); // Will trigger file deletion via model event
        }

        $post->delete();

        return redirect()->route('marketing.social-planner.index')
            ->with('success', 'Post deleted successfully!');
    }

    public function publish($id)
    {
        $post = SocialPost::forTenant(auth()->user()->tenant_id)->findOrFail($id);
        
        if (!$post->canBePublished()) {
            return redirect()->back()->with('error', 'Post cannot be published in its current state.');
        }

        // Dispatch job to publish
        PublishSocialPostJob::dispatch($post);
        
        $post->update(['status' => 'publishing']);

        return redirect()->back()->with('success', 'Post is being published...');
    }

    public function approve($id)
    {
        $post = SocialPost::forTenant(auth()->user()->tenant_id)->findOrFail($id);
        
        $post->update([
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'status' => $post->scheduled_for ? 'scheduled' : 'approved',
        ]);

        $post->approvals()->create([
            'user_id' => auth()->id(),
            'tenant_id' => auth()->user()->tenant_id,
            'action' => 'approved',
            'previous_status' => $post->status,
            'new_status' => 'approved',
        ]);

        // Auto-publish if enabled and not scheduled
        $settings = SocialPlannerSettings::getOrCreateForTenant(auth()->user()->tenant_id);
        if ($settings->auto_publish_approved && !$post->scheduled_for) {
            PublishSocialPostJob::dispatch($post);
            $post->update(['status' => 'publishing']);
        }

        return redirect()->back()->with('success', 'Post approved!');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $post = SocialPost::forTenant(auth()->user()->tenant_id)->findOrFail($id);
        
        $post->update([
            'approval_status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $request->reason,
            'status' => 'rejected',
        ]);

        $post->approvals()->create([
            'user_id' => auth()->id(),
            'tenant_id' => auth()->user()->tenant_id,
            'action' => 'rejected',
            'comment' => $request->reason,
            'previous_status' => $post->status,
            'new_status' => 'rejected',
        ]);

        return redirect()->back()->with('success', 'Post rejected.');
    }

    public function analytics($id)
    {
        $post = SocialPost::forTenant(auth()->user()->tenant_id)
            ->with(['postChannels.analytics', 'postChannels.channel'])
            ->findOrFail($id);

        $analyticsData = $post->postChannels->map(function ($postChannel) {
            return [
                'channel' => $postChannel->channel->name,
                'platform' => $postChannel->channel->type,
                'likes' => $postChannel->analytics->likes ?? 0,
                'comments' => $postChannel->analytics->comments ?? 0,
                'shares' => $postChannel->analytics->shares ?? 0,
                'clicks' => $postChannel->analytics->clicks ?? 0,
                'impressions' => $postChannel->analytics->impressions ?? 0,
                'reach' => $postChannel->analytics->reach ?? 0,
                'engagement_rate' => $postChannel->analytics->engagement_rate ?? 0,
                'last_synced' => $postChannel->analytics->last_synced_at ?? null,
            ];
        });

        return Inertia::render('Marketing/SocialPlanner/Analytics', [
            'post' => $post,
            'analytics' => $analyticsData,
        ]);
    }
}
