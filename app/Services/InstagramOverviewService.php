<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Conversation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramOverviewService
{
    private const GRAPH_VERSION = 'v24.0';

    /**
     * Get overview data for an Instagram Channel
     */
    public function getOverviewData(Channel $channel, int $days = 30)
    {
        $startDate = now()->subDays($days);
        $endDate = now();

        return [
            'metrics' => $this->calculateMetrics($channel, $startDate, $endDate),
            'posts' => $this->fetchRecentPosts($channel),
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
        $messageStats = \App\Models\Message::whereHas('conversation', function($q) use ($channel) {
                $q->where('channel_id', $channel->id);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("
                COUNT(CASE WHEN direction = 'inbound' THEN 1 END) as received,
                COUNT(CASE WHEN direction = 'outbound' THEN 1 END) as sent
            ")
            ->first();

        // 3. Activity Chart Data (Last 30 days)
        $dailyActivity = $this->getDailyActivity($channel, $startDate, $endDate);

        return [
            'total_conversations' => $totalConversations,
            'conversation_trend' => $trend,
            'messages_received' => $messageStats->received ?? 0,
            'messages_sent' => $messageStats->sent ?? 0,
            'avg_response_time' => 'N/A', // TODO: Implement if needed
            'daily_activity' => $dailyActivity
        ];
    }

    /**
     * Fetch recent posts from Instagram Graph API
     */
    private function fetchRecentPosts(Channel $channel)
    {
        $accessToken = $channel->config['access_token'] ?? null;
        $instagramId = $channel->config['instagram_id'] ?? null;
        
        if (!$accessToken || !$instagramId) return [];

        try {
            // Fetch Instagram media (posts)
            $response = Http::get("https://graph.facebook.com/" . self::GRAPH_VERSION . "/{$instagramId}/media", [
                'access_token' => $accessToken,
                'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count',
                'limit' => 9
            ]);

            if ($response->successful()) {
                $posts = $response->json()['data'] ?? [];
                
                return array_map(function($post) {
                    return [
                        'id' => $post['id'],
                        'content' => $post['caption'] ?? 'No caption',
                        'image_url' => $post['media_type'] === 'VIDEO' 
                            ? ($post['thumbnail_url'] ?? $post['media_url'] ?? null)
                            : ($post['media_url'] ?? null),
                        'created_at' => $post['timestamp'] ?? now(),
                        'url' => $post['permalink'] ?? "https://instagram.com/p/{$post['id']}",
                        'likes' => $post['like_count'] ?? 0,
                        'comments' => $post['comments_count'] ?? 0,
                        'media_type' => $post['media_type'] ?? 'IMAGE',
                    ];
                }, $posts);
            }
            
            Log::warning('Instagram Posts Fetch Failed', ['status' => $response->status(), 'body' => $response->body()]);
            return [];
        } catch (\Exception $e) {
            Log::error('Instagram Posts Exception', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Helper for daily chart
     */
    private function getDailyActivity($channel, $startDate, $endDate)
    {
        $data = [];
        $current = $startDate->copy();
        
        // Pre-fetch counts grouped by date
        $customStats = \App\Models\Message::whereHas('conversation', function($q) use ($channel) {
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
     * Get Instagram account details
     */
    private function getAccountDetails($channel)
    {
        return [
           'id' => $channel->config['instagram_id'] ?? '',
           'name' => $channel->name,
           'picture' => $channel->config['profile_picture_url'] ?? null,
           'link' => "https://instagram.com/" . ($channel->name ?? ''),
        ];
    }
}
