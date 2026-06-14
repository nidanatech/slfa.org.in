<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Message;
use App\Models\ConversationReferral;
use Illuminate\Support\Facades\Log;

class BackfillReferrals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inbox:backfill-referrals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill referral data from message metadata into the conversation_referrals table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting backfill of referral data...');

        // Query messages that have potential referral data in meta
        // We look for both raw_event structure and simpler referral structure
        $messages = Message::whereNotNull('meta')->get();
        $totalProcessed = 0;
        $totalCreated = 0;

        foreach ($messages as $message) {
            $totalProcessed++;
            $meta = $message->meta;
            
            // Extract referral data from common paths in meta
            $referralData = null;
            
            if (isset($meta['raw_event']['message']['referral'])) {
                $referralData = $meta['raw_event']['message']['referral'];
            } elseif (isset($meta['referral'])) {
                $referralData = $meta['referral'];
            } elseif (isset($meta['raw_event']['referral'])) {
                $referralData = $meta['raw_event']['referral'];
            }

            if ($referralData) {
                $conversation = $message->conversation;
                
                if (!$conversation) {
                    $this->warn("Message ID {$message->id} has referral data but no associated conversation.");
                    continue;
                }

                // Only create if it doesn't already exist
                if (!$conversation->referral()->exists()) {
                    try {
                        ConversationReferral::create([
                            'conversation_id' => $conversation->id,
                            'tenant_id' => $conversation->tenant_id,
                            'source' => $referralData['source'] ?? null,
                            'type' => $referralData['type'] ?? null,
                            'ref' => $referralData['ref'] ?? null,
                            'ad_id' => $referralData['ad_id'] ?? null,
                            'ad_title' => $referralData['ads_context_data']['ad_title'] ?? null,
                            'post_id' => $referralData['ads_context_data']['post_id'] ?? null,
                            'photo_url' => $referralData['ads_context_data']['photo_url'] ?? null,
                            'metadata' => $referralData,
                        ]);
                        $totalCreated++;
                        
                        $this->line("Created referral for conversation ID {$conversation->id} from message ID {$message->id}");

                        // Trigger contact profile sync for this contact if it's a placeholder
                        if ($conversation->contact) {
                            \Illuminate\Support\Facades\Artisan::call('inbox:sync-contact-profiles', [
                                '--contact' => $conversation->contact->id
                            ]);
                        }
                    } catch (\Exception $e) {
                        $this->error("Failed to create referral for conversation ID {$conversation->id}: " . $e->getMessage());
                    }
                }
            }
        }

        $this->info("Backfill complete!");
        $this->info("Processed: {$totalProcessed} messages");
        $this->info("Created: {$totalCreated} referral records");
    }
}
