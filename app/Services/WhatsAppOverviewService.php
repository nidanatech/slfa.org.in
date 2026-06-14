<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class WhatsAppOverviewService
{
    /**
     * Get overview data for a WhatsApp Channel
     */
    public function getOverviewData(Channel $channel, int $days = 30)
    {
        $startDate = now()->subDays($days);
        $endDate = now();

        return [
            'metrics' => $this->calculateMetrics($channel, $startDate, $endDate),
            'account' => $this->getAccountDetails($channel)
        ];
    }

    /**
     * Calculate metrics from internal conversations
     */
    private function calculateMetrics(Channel $channel, $startDate, $endDate)
    {
        // 1. Total Conversations
        $conversations = Conversation::where('channel_id', $channel->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $totalConversations = $conversations->count();
        
        // Trend (previous period)
        $previousStartDate = $startDate->copy()->subDays($startDate->diffInDays($endDate));
        $previousConversations = Conversation::where('channel_id', $channel->id)
            ->whereBetween('created_at', [$previousStartDate, $startDate])
            ->count();

        $trend = $previousConversations > 0 
            ? round((($totalConversations - $previousConversations) / $previousConversations) * 100)
            : ($totalConversations > 0 ? 100 : 0);

        // 2. Messages Volume (Sent vs Received)
        $messageStats = Message::whereHas('conversation', function($q) use ($channel) {
                $q->where('channel_id', $channel->id);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("
                COUNT(CASE WHEN direction = 'inbound' THEN 1 END) as received,
                COUNT(CASE WHEN direction = 'outbound' THEN 1 END) as sent
            ")
            ->first();

        // 3. Activity Chart Data
        $dailyActivity = $this->getDailyActivity($channel, $startDate, $endDate);

        return [
            'total_conversations' => $totalConversations,
            'conversation_trend' => $trend,
            'messages_received' => $messageStats->received ?? 0,
            'messages_sent' => $messageStats->sent ?? 0,
            'avg_response_time' => 'N/A',
            'daily_activity' => $dailyActivity
        ];
    }

    /**
     * Helper for daily chart
     */
    private function getDailyActivity($channel, $startDate, $endDate)
    {
        $data = [];
        $current = $startDate->copy();
        
        // Pre-fetch counts grouped by date
        $customStats = Message::whereHas('conversation', function($q) use ($channel) {
                $q->where('channel_id', $channel->id);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("DATE(created_at) as date, COUNT(*) as count")
            ->groupBy('date')
            ->pluck('count', 'date');

        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $data[] = [
                'date' => $current->format('M d'),
                'messages' => $customStats[$dateKey] ?? 0
            ];
            $current->addDay();
        }
        
        return $data;
    }

    /**
     * Get WhatsApp account details
     */
    private function getAccountDetails($channel)
    {
        if ($channel->type === 'whatsapp_api') {
            return [
                'id' => $channel->config['phone_number_id'] ?? 'N/A',
                'name' => $channel->name,
                'picture' => null,
                'type' => 'Official API',
                'connection_status' => 'active'
            ];
        }

        return [
            'id' => $channel->config['whatsapp_id'] ?? $channel->config['session_id'] ?? 'N/A',
            'name' => $channel->config['profile_name'] ?? $channel->name,
            'picture' => $channel->config['profile_picture_url'] ?? null,
            'type' => 'WhatsApp Web',
            'connection_status' => $channel->is_active ? 'connected' : 'disconnected'
        ];
    }
}
