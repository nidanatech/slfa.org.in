<?php

namespace App\Services;

use App\Models\AiBot;
use App\Models\Message;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class BotService
{
    /**
     * Find an active bot for a specific tenant and channel type.
     *
     * @param Tenant $tenant
     * @param string $channelType
     * @return AiBot|null
     */
    public function findActiveBot(Tenant $tenant, \App\Models\Channel $channel): ?AiBot
    {
        $channelType = $channel->type;
        Log::info("BotService: Finding active bot for Tenant {$tenant->id}, Channel ID: {$channel->id} ({$channelType})");


        // 1. First priority: Primary Bot if it supports the channel
        $primaryBot = $tenant->bots()
            ->where('is_active', true)
            ->where('is_primary', true)
            ->first();

        if ($primaryBot) {
            if ($this->botSupportsChannel($primaryBot, $channel)) {
                Log::info("BotService: Primary bot {$primaryBot->id} selected.");
                return $primaryBot;
            } else {
                Log::info("BotService: Primary bot {$primaryBot->id} found but doesn't support channel {$channel->id}.");
            }
        }

        // 2. Second priority: Any active bot that supports the channel
        $activeBots = $tenant->bots()
            ->where('is_active', true)
            ->get();

        Log::info("BotService: Found {$activeBots->count()} active bots for tenant.");

        $matchedBot = $activeBots->filter(fn($bot) => $this->botSupportsChannel($bot, $channel))->first();

        if ($matchedBot) {
            Log::info("BotService: Selected bot {$matchedBot->id} for channel {$channel->id}.");
        } else {
            Log::info("BotService: No bot matched channel ID {$channel->id}. Status: " . json_encode([
                'channel_id' => $channel->id,
                'channel_type' => $channelType,
                'active_bots_count' => $activeBots->count(),
                'active_bot_ids' => $activeBots->pluck('id')->toArray()
            ]));
        }

        return $matchedBot;
    }

    /**
     * Trigger a bot response for a given message.
     *
     * @param Message $message
     * @return void
     */
    public function triggerResponse(Message $message): void
    {
        Log::info("BotService: triggerResponse initiated for Message ID: {$message->id}");
        try {
            $conversation = $message->conversation;
            if (!$conversation) {
                Log::warning("BotService: Message {$message->id} has no conversation.");
                return;
            }

            $tenant = $message->tenant ?? $conversation->tenant;
            if (!$tenant) {
                Log::warning("BotService: Message {$message->id} has no tenant (via conversation {$conversation->id}).");
                return;
            }

            $channel = $conversation->channel;
            if (!$channel) {
                Log::warning("BotService: Conversation {$conversation->id} has no channel.");
                return;
            }

            $bot = $this->findActiveBot($tenant, $channel);

            if ($bot) {
                Log::info("BotService: Triggering response for Bot {$bot->id} on channel {$channel->type}");
                $bot->processBotResponse($message);
            } else {
                Log::info("BotService: No active bot found for channel {$channel->type} in tenant {$tenant->id}");
            }
        } catch (\Exception $e) {
            Log::error("BotService: Failed to trigger bot response: " . $e->getMessage(), [
                'message_id' => $message->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Check if a bot supports a specific channel type.
     *
     * @param AiBot $bot
     * @param string $channelType
     * @return bool
     */
    protected function botSupportsChannel(AiBot $bot, \App\Models\Channel $channel): bool
    {
        $supportedChannels = $bot->supported_channels ?? [];
        $channelId = (string) $channel->id;
        $channelType = $channel->type;
        
        Log::info("BotService: Checking Bot {$bot->id} support for Channel {$channelId} ({$channelType})", [
            'supported_channels' => $supportedChannels
        ]);

        // 1. Check for specific Channel ID (Primary usage)
        if (in_array((int)$channelId, $supportedChannels) || in_array($channelId, $supportedChannels)) {
            Log::info("BotService: Bot {$bot->id} MATCHED on Channel ID '{$channelId}'");
            return true;
        }

        // 2. Handle "all" case
        if (in_array('all', $supportedChannels) || in_array('all_channels', $supportedChannels)) {
            Log::info("BotService: Bot {$bot->id} MATCHED on 'all'");
            return true;
        }

        // 3. Map internal channel types to common names used in bot config (Fallback)
        $mapping = [
            'messenger' => ['facebook', 'facebook_messenger', 'messenger'],
            'google_business' => ['google', 'gmb', 'google_business'],
            'api' => ['wordpress', 'website', 'api'],
            'instagram' => ['instagram'],
            'instagram_message' => ['instagram'],
            'web' => ['web', 'website', 'widget'],
            'whatsapp' => ['whatsapp', 'whatsapp_api'],
            'whatsapp_api' => ['whatsapp', 'whatsapp_api'],
        ];

        $aliases = $mapping[$channelType] ?? [$channelType];
        
        foreach ($aliases as $alias) {
            if (in_array($alias, $supportedChannels)) {
                Log::info("BotService: Bot {$bot->id} MATCHED on alias '{$alias}'");
                return true;
            }
        }

        // 4. Special Fallback for Primary Bots: If they supported the "type" previously but the ID changed (due to reconnection)
        if ($bot->is_primary) {
             // For primary bots, we can be more permissive if they're the only bot
             // or if they were explicitly configured to support the platform type.
             // We've already checked aliases above.
             Log::info("BotService: Primary Bot {$bot->id} did not match channel {$channelId} by ID or Type Alias.");
        }

        Log::info("BotService: Bot {$bot->id} DID NOT match channel {$channelId} / type {$channelType}.");
        return false;
    }
}
