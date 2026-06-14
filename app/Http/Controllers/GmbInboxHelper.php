<?php

namespace App\Http\Controllers;

use App\Models\GmbReview;
use App\Models\Channel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\AiBot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class GmbInboxHelper
{
    /**
     * Sync GMB review to inbox (create conversation, contact, messages)
     */
    public static function syncReviewToInbox($gmbReview, $channel, $reviewerEmail = null, $isNewReview = false)
    {
        try {
            // 1. Find or Create Contact for Reviewer
            $externalId = 'gmb_review_' . $gmbReview->review_id;
            
            // Helper to safe parse rating
            $starRating = $gmbReview->star_rating;
            if (!is_numeric($starRating)) {
                 $ratingMap = [
                    'FIVE' => 5,
                    'FOUR' => 4,
                    'THREE' => 3,
                    'TWO' => 2,
                    'ONE' => 1,
                ];
                $val = strtoupper((string)$starRating);
                $starRating = $ratingMap[$val] ?? 0;
            }

            $contactData = [
                'tenant_id' => $channel->tenant_id,
                'channel_id' => $channel->id,
                'external_id' => $externalId,
                'name' => $gmbReview->reviewer_display_name ?? 'Anonymous Reviewer',
                'avatar_url' => $gmbReview->reviewer_profile_photo_url,
                'rating' => (int)$starRating,
                'meta' => [
                    'source' => 'gmb_review',
                    'review_id' => $gmbReview->review_id,
                ],
            ];
            
            // Add email if available
            if ($reviewerEmail) {
                $contactData['emails'] = [['value' => $reviewerEmail, 'type' => 'personal']];
            }
            
            $contact = Contact::updateOrCreate(
                ['external_id' => $externalId],
                $contactData
            );
            
            // 2. Find or Create Conversation
            $reviewCreatedAt = $gmbReview->create_time ?? now();
            $lastActivityAt = $gmbReview->reply_update_time ?? $reviewCreatedAt;

            $conversation = Conversation::firstOrCreate(
                [
                    'tenant_id' => $channel->tenant_id,
                    'channel_id' => $channel->id,
                    'contact_id' => $contact->id,
                ],
                [
                    'conversation_type' => 'external',
                    'status' => $gmbReview->review_reply ? 'closed' : 'open',
                    'last_message_at' => $lastActivityAt,
                    'created_at' => $reviewCreatedAt,
                ]
            );
            
            // Store review metadata in conversation
            $conversation->update([
                'meta' => array_merge($conversation->meta ?? [], [
                    'review_id' => $gmbReview->review_id,
                    'star_rating' => $gmbReview->star_rating,
                ]),
                // Ensure last_message_at is up to date if conversation existed but review was updated/replied
                'last_message_at' => $lastActivityAt
            ]);
            
            // 3. Create Inbound Message for Review Comment (if not exists)
            $existingInboundMessage = Message::where('conversation_id', $conversation->id)
                ->where('direction', 'inbound')
                ->where('meta->review_id', $gmbReview->review_id)
                ->first();
            
            if (!$existingInboundMessage && $gmbReview->comment) {
                $inboundMessage = Message::create([
                    'conversation_id' => $conversation->id,
                    'direction' => 'inbound',
                    'type' => 'text',
                    'content' => $gmbReview->comment,
                    'sender_type' => 'contact',
                    'meta' => [
                        'review_id' => $gmbReview->review_id,
                        'star_rating' => $gmbReview->star_rating,
                        'create_time' => $gmbReview->create_time,
                    ],
                    'created_at' => $reviewCreatedAt,
                ]);
                
                // 4. AI Auto-Reply (Only if manually enabled in channel config)
                if ($channel->config['auto_reply_enabled'] ?? false) {
                    if ($isNewReview && !$gmbReview->review_reply) {
                        self::triggerAiAutoReply($conversation, $inboundMessage, $gmbReview, $channel);
                    }
                }
            }
            
            // 5. Create Outbound Message for Review Reply (if exists and not already created)
            if ($gmbReview->review_reply) {
                $existingOutboundMessage = Message::where('conversation_id', $conversation->id)
                    ->where('direction', 'outbound')
                    ->where('meta->is_review_reply', true)
                    ->first();
                
                if (!$existingOutboundMessage) {
                    Message::create([
                        'conversation_id' => $conversation->id,
                        'direction' => 'outbound',
                        'type' => 'text',
                        'content' => $gmbReview->review_reply,
                        'sender_type' => 'user',
                        'delivered_at' => $gmbReview->reply_update_time ?? now(),
                        'meta' => [
                            'is_review_reply' => true,
                            'review_id' => $gmbReview->review_id,
                        ],
                        'created_at' => $gmbReview->reply_update_time ?? now(),
                    ]);
                    
                    $conversation->update([
                        'status' => 'closed',
                        'last_message_at' => $gmbReview->reply_update_time ?? now(),
                    ]);
                }
            }
            
            Log::info('GMB Review Synced to Inbox', [
                'review_id' => $gmbReview->review_id,
                'conversation_id' => $conversation->id,
                'contact_id' => $contact->id,
                'is_new' => $isNewReview,
            ]);
            
        } catch (\Exception $e) {
            Log::error('GMB Review Inbox Sync Error', [
                'review_id' => $gmbReview->review_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Trigger AI auto-reply for new GMB review
     */
    private static function triggerAiAutoReply($conversation, $inboundMessage, $gmbReview, $channel)
    {
        try {
            // Find active AI bot for this channel using centralized BotService
            $botService = new \App\Services\BotService();
            $bot = $botService->findActiveBot($channel->tenant, $channel);
            
            if (!$bot || !$bot->assistant_id) {
                Log::info('No active AI bot found for GMB channel', ['channel_id' => $channel->id]);
                return;
            }
            
            // Get response instructions based on star rating
            $instructions = self::getResponseInstructions($gmbReview->star_rating, $channel);
            
            Log::info('Triggering AI Auto-Reply for GMB Review', [
                'review_id' => $gmbReview->review_id,
                'bot_id' => $bot->id,
                'star_rating' => $gmbReview->star_rating,
                'has_instructions' => !empty($instructions),
            ]);
            
            // Temporarily modify message content to include instructions for AI
            $originalContent = $inboundMessage->content;
            if ($instructions) {
                $enhancedContent = "REVIEW RESPONSE INSTRUCTIONS:\n{$instructions}\n\n";
                $enhancedContent .= "REVIEWER NAME: {$gmbReview->reviewer_display_name}\n";
                $enhancedContent .= "STAR RATING: {$gmbReview->star_rating} stars\n";
                $enhancedContent .= "REVIEW CONTENT:\n{$originalContent}";
                
                // Temporarily update message content
                $inboundMessage->content = $enhancedContent;
            }
            
            // Process bot response (uses HandlesBotConversations trait)
            $bot->processBotResponse($inboundMessage);
            
            // Restore original content
            $inboundMessage->content = $originalContent;
            $inboundMessage->save();
            
            // After bot processes, check if a reply was generated
            $botReply = Message::where('conversation_id', $conversation->id)
                ->where('direction', 'outbound')
                ->where('sender_type', 'bot')
                ->latest()
                ->first();
            
            if ($botReply) {
                // Post the bot's reply to Google My Business
                self::postReviewReply($gmbReview, $botReply->content, $channel);
            }
            
        } catch (\Exception $e) {
            Log::error('GMB AI Auto-Reply Error', [
                'review_id' => $gmbReview->review_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get response instructions based on star rating
     */
    private static function getResponseInstructions($starRating, $channel)
    {
        // Try to fetch custom instructions from database
        $template = \App\Models\GmbReviewTemplate::where('channel_id', $channel->id)
            ->where('star_rating', $starRating)
            ->where('is_active', true)
            ->first();
        
        if ($template) {
            return $template->instructions;
        }
        
        // Fallback to default instructions if no custom template exists
        $defaultInstructions = [
            5 => 'Warmly thank the reviewer by name. Express genuine appreciation for their positive feedback. Highlight a specific aspect they mentioned if possible. Invite them to visit again and suggest they might enjoy a new offering or seasonal item. Keep the tone enthusiastic and grateful.',
            4 => 'Thank the reviewer by name for their feedback. Acknowledge the positive aspects they mentioned. Politely inquire if there\'s anything that could have made their experience a 5-star one. Show willingness to improve and invite them back to experience any improvements made.',
            3 => 'Apologize sincerely for their disappointing experience. Acknowledge their specific concerns if mentioned. Take responsibility without making excuses. Offer a direct way to resolve the issue (phone number, email, or manager contact). Express commitment to improvement and invite them to give you another chance.',
            2 => 'Apologize sincerely for their disappointing experience. Acknowledge their specific concerns if mentioned. Take responsibility without making excuses. Offer a direct way to resolve the issue (phone number, email, or manager contact). Express commitment to improvement and invite them to give you another chance.',
            1 => 'Apologize sincerely for their disappointing experience. Acknowledge their specific concerns if mentioned. Take responsibility without making excuses. Offer a direct way to resolve the issue (phone number, email, or manager contact). Express commitment to improvement and invite them to give you another chance.',
        ];
        
        return $defaultInstructions[$starRating] ?? $defaultInstructions[3];
    }

    /**
     * Post reply to Google My Business review
     */
    public static function postReviewReply($gmbReview, $replyText, $channel)
    {
        try {
            $accessToken = $channel->config['access_token'];
            $accountName = $channel->config['account_name'];
            $locationName = $channel->config['location_name'];
            
            // The review name format: accounts/{accountId}/locations/{locationId}/reviews/{reviewId}
            $reviewName = "{$accountName}/{$locationName}/reviews/{$gmbReview->review_id}";
            
            $response = Http::withToken($accessToken)->put(
                "https://mybusiness.googleapis.com/v4/{$reviewName}/reply",
                [
                    'comment' => $replyText
                ]
            );
            
            if ($response->successful()) {
                // Update GMB review record with the reply
                $gmbReview->update([
                    'review_reply' => $replyText,
                    'reply_update_time' => now(),
                ]);
                
                Log::info('GMB Review Reply Posted', [
                    'review_id' => $gmbReview->review_id,
                    'reply_length' => strlen($replyText),
                ]);
            } else {
                Log::error('GMB Review Reply Failed', [
                    'review_id' => $gmbReview->review_id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('GMB Review Reply Post Error', [
                'review_id' => $gmbReview->review_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
