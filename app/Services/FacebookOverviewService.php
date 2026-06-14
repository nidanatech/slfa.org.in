<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Conversation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookOverviewService
{
    private const GRAPH_VERSION = 'v24.0';

    /**
     * Get overview data for a Facebook Channel
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
        // We need to join messages table
        $messageStats = \App\Models\Message::whereHas('conversation', function($q) use ($channel) {
                $q->where('channel_id', $channel->id);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("
                COUNT(CASE WHEN direction = 'inbound' THEN 1 END) as received,
                COUNT(CASE WHEN direction = 'outbound' THEN 1 END) as sent
            ")
            ->first();

        // 3. Avg Response Time (Simple approximation: Time between Inbound and next Outbound)
        // For accurate calc, we'd need a more complex query or dedicated stats table. 
        // For now, let's use a placeholder or a simple avg if available field.
        // We'll calculate a simple "Messages per day" instead for the chart.
        
        // 4. Activity Chart Data (Last 30 days)
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
     * fetch recent posts from Graph API
     */
    private function fetchRecentPosts(Channel $channel)
    {
        $accessToken = $channel->config['access_token'] ?? null;
        if (!$accessToken) return [];

        try {
            // Fetch posts (feed)
            $response = Http::get("https://graph.facebook.com/" . self::GRAPH_VERSION . "/me/feed", [
                'access_token' => $accessToken,
                'fields' => 'id,message,created_time,full_picture,permalink_url,status_type,story,shares,likes.summary(true),comments.summary(true)',
                'limit' => 5
            ]);

            if ($response->successful()) {
                $posts = $response->json()['data'] ?? [];
                
                return array_map(function($post) use ($channel) {
                    return [
                        'id' => $post['id'],
                        'content' => $post['message'] ?? $post['story'] ?? 'No content',
                        'image_url' => $post['full_picture'] ?? null,
                        'created_at' => $post['created_time'],
                        'url' => $post['permalink_url'] ?? "https://facebook.com/{$post['id']}",
                        'likes' => $post['likes']['summary']['total_count'] ?? 0,
                        'comments' => $post['comments']['summary']['total_count'] ?? 0,
                        'shares' => $post['shares']['count'] ?? 0,
                    ];
                }, $posts);
            }
            
            Log::warning('FB Posts Fetch Failed', ['status' => $response->status(), 'body' => $response->body()]);
            return [];
        } catch (\Exception $e) {
            Log::error('FB Posts Exception', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    // Helper for daily chart
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

    private function getAccountDetails($channel)
    {
        return [
           'id' => $channel->config['page_id'] ?? '',
           'name' => $channel->name,
           'picture' => $channel->config['profile_picture_url'] ?? null,
           'link' => "https://facebook.com/" . ($channel->config['page_id'] ?? ''),
        ];
    }
}
