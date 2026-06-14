<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Channel;
use App\Services\FacebookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateFacebookContactNames implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting Facebook Contact Names Update Job');

        // Get all Facebook Messenger channels
        $facebookChannels = Channel::where('type', 'messenger')
            ->where('is_active', true)
            ->get();

        if ($facebookChannels->isEmpty()) {
            Log::warning('No active Facebook Messenger channels found');
            return;
        }

        $totalUpdated = 0;
        $totalFailed = 0;

        foreach ($facebookChannels as $channel) {
            Log::info("Processing channel: {$channel->name} (ID: {$channel->id})");

            // Get last 50 contacts for this channel
            $contacts = Contact::where('channel_id', $channel->id)
                ->whereNotNull('external_id')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            if ($contacts->isEmpty()) {
                Log::info("No contacts found for channel {$channel->name}");
                continue;
            }

            $facebookService = new FacebookService($channel);

            foreach ($contacts as $contact) {
                try {
                    // Fetch user profile from Facebook
                    $profile = $facebookService->getUserProfile($contact->external_id);

                    if (!$profile) {
                        Log::warning("Failed to fetch profile for contact {$contact->id} (PSID: {$contact->external_id})");
                        $totalFailed++;
                        continue;
                    }

                    // Prepare update data
                    $updateData = [];

                    if (!empty($profile['first_name'])) {
                        $updateData['first_name'] = $profile['first_name'];
                    }

                    if (!empty($profile['last_name'])) {
                        $updateData['last_name'] = $profile['last_name'];
                    }

                    // Update full name
                    if (!empty($profile['name'])) {
                        $updateData['name'] = $profile['name'];
                    } elseif (!empty($updateData['first_name']) || !empty($updateData['last_name'])) {
                        $updateData['name'] = trim(($updateData['first_name'] ?? '') . ' ' . ($updateData['last_name'] ?? ''));
                    }

                    // Update profile picture
                    if (!empty($profile['profile_pic'])) {
                        $updateData['avatar_url'] = $profile['profile_pic'];
                    }

                    // Only update if we have data
                    if (!empty($updateData)) {
                        $contact->update($updateData);
                        $totalUpdated++;
                        
                        Log::info("Updated contact {$contact->id}: " . json_encode($updateData));
                    } else {
                        Log::info("No new data to update for contact {$contact->id}");
                    }

                    // Small delay to avoid rate limiting
                    usleep(100000); // 100ms delay

                } catch (\Exception $e) {
                    Log::error("Error updating contact {$contact->id}: " . $e->getMessage());
                    $totalFailed++;
                }
            }
        }

        Log::info("Facebook Contact Names Update Job Completed", [
            'total_updated' => $totalUpdated,
            'total_failed' => $totalFailed
        ]);
    }
}
