<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Channel;
use Exception;

class GoogleBusinessChannelController extends Controller
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    
    // GMB APIs are split
    private const ACCOUNTS_API = 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts';
    private const LOCATIONS_BATCH_API = 'https://mybusinessbusinessinformation.googleapis.com/v1';

    private const SCOPES = [
        'https://www.googleapis.com/auth/business.manage',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile',
    ];

    public function connect()
    {
        $params = [
            'client_id' => config('services.google_business.client_id'),
            'redirect_uri' => config('services.google_business.redirect_uri'),
            'response_type' => 'code',
            'scope' => implode(' ', self::SCOPES),
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];

        return redirect(self::AUTH_URL . '?' . http_build_query($params));
    }

    public function callback(Request $request)
    {
        Log::info('GMB Callback Started', [
            'has_code' => $request->has('code'),
            'has_error' => $request->has('error'),
            'all_params' => $request->all()
        ]);

        try {
            if ($request->has('error')) {
                Log::error('GMB Auth Error', ['error' => $request->all()]);
                return redirect()->route('channels.index')->with('error', 'Google Business connection failed.');
            }

            $code = $request->query('code');
            if (!$code) {
                Log::error('GMB Callback: No authorization code received');
                return redirect()->route('channels.index')->with('error', 'No authorization code received.');
            }

            $redirectUri = config('services.google_business.redirect_uri');
            
            Log::info('GMB Token Exchange', [
                'client_id' => config('services.google_business.client_id'),
                'redirect_uri' => $redirectUri,
                'has_client_secret' => !empty(config('services.google_business.client_secret'))
            ]);

            // Exchange Code
            $response = Http::asForm()->post(self::TOKEN_URL, [
                'client_id' => config('services.google_business.client_id'),
                'client_secret' => config('services.google_business.client_secret'),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
            ]);

            $data = $response->json();
            
            Log::info('GMB Token Response', [
                'status' => $response->status(),
                'has_access_token' => isset($data['access_token']),
                'has_refresh_token' => isset($data['refresh_token']),
                'response_keys' => array_keys($data)
            ]);

            if (!isset($data['access_token'])) {
                Log::error('GMB Token Error', ['response' => $data]);
                return redirect()->route('channels.index')->with('error', 'Failed to exchange GMB token.');
            }

            $accessToken = $data['access_token'];
            $refreshToken = $data['refresh_token'] ?? null;
            $expiresIn = $data['expires_in'] ?? null;

            // 1. Fetch Accounts
            Log::info('GMB Fetching Accounts', ['api_url' => self::ACCOUNTS_API]);
            $accountsRes = Http::withToken($accessToken)->get(self::ACCOUNTS_API);
            $accounts = $accountsRes->json()['accounts'] ?? [];
            
            Log::info('GMB Accounts Fetched', [
                'status' => $accountsRes->status(),
                'account_count' => count($accounts),
            ]);

            if (empty($accounts)) {
                Log::warning('GMB No Accounts Found');
                return redirect()->route('channels.index')->with('error', 'No Google Business Accounts found.');
            }

            $discoveredLocations = [];

            foreach ($accounts as $account) {
                $accountName = $account['name']; // e.g., "accounts/111..."
                
                // 2. Fetch Locations for this Account
                $locationsRes = Http::withToken($accessToken)->get(self::LOCATIONS_BATCH_API . '/' . $accountName . '/locations', [
                    'readMask' => 'name,title,storefrontAddress,phoneNumbers,websiteUri,categories,profile',
                ]);
                
                $locations = $locationsRes->json()['locations'] ?? [];

                foreach ($locations as $location) {
                    $locationName = $location['name']; // e.g., "locations/222..."
                    $title = $location['title'] ?? 'Business Location';

                    // Extract business details for display in selection
                    $address = 'No address provided';
                    if (isset($location['storefrontAddress'])) {
                        $addr = $location['storefrontAddress'];
                        $addressParts = array_filter([
                            $addr['addressLines'][0] ?? '',
                            $addr['locality'] ?? '',
                            $addr['administrativeArea'] ?? '',
                            $addr['postalCode'] ?? '',
                        ]);
                        $address = implode(', ', $addressParts);
                    }

                    $discoveredLocations[] = [
                        'account_name' => $accountName,
                        'location_name' => $locationName, 
                        'business_name' => $title,
                        'address' => $address,
                        'category' => $location['categories']['primaryCategory']['displayName'] ?? 'Uncategorized',
                        'profile_photo_url' => $location['profile']['profilePhotoUrl'] ?? null,
                        'already_connected' => Auth::user()->tenant->channels()
                            ->where('type', 'google_business')
                            ->where('config->location_name', $locationName)
                            ->exists()
                    ];
                }
            }

            // Store in session for selection step
            session([
                'gmb_connect_data' => [
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'expires_in' => $expiresIn,
                    'locations' => $discoveredLocations
                ]
            ]);

            return redirect()->route('channels.google_business.select');

        } catch (Exception $e) {
            Log::error('GMB Callback Exception', ['message' => $e->getMessage()]);
            return redirect()->route('channels.index')->with('error', 'Error connecting Google Business: ' . $e->getMessage());
        }
    }

    /**
     * Show location selection screen
     */
    public function select()
    {
        $data = session('gmb_connect_data');
        if (!$data || empty($data['locations'])) {
            return redirect()->route('channels.index')->with('error', 'No GMB locations found to select.');
        }

        return \Inertia\Inertia::render('Channels/GmbSelection', [
            'locations' => $data['locations']
        ]);
    }

    /**
     * Store selected locations as channels
     */
    public function storeSelected(Request $request)
    {
        $selectedLocationNames = $request->input('selected_locations', []);
        $data = session('gmb_connect_data');

        if (!$data || empty($selectedLocationNames)) {
            return redirect()->route('channels.index')->with('error', 'No locations selected.');
        }

        $accessToken = $data['access_token'];
        $refreshToken = $data['refresh_token'];
        $expiresIn = $data['expires_in'];
        $allLocations = collect($data['locations']);
        $tenant = Auth::user()->tenant;
        $count = 0;

        foreach ($selectedLocationNames as $locationName) {
            $location = $allLocations->firstWhere('location_name', $locationName);
            if (!$location) continue;

            $config = [
                'account_name' => $location['account_name'],
                'location_name' => $locationName,
                'business_name' => $location['business_name'],
                'address' => $location['address'],
                'category' => $location['category'],
                'profile_photo_url' => $location['profile_photo_url'],
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_at' => now()->addSeconds($expiresIn ?? 3600)->toDateTimeString(),
                'connected_at' => now()->toDateTimeString(),
            ];

            $channelService = new \App\Services\ChannelService();
            $channel = $channelService->registerConnection(
                $tenant->id,
                'google_business',
                $location['business_name'] . ' (GMB)',
                $config,
                'location_name'
            );

            // Auto-fetch Initial Data
            try {
                $this->fetchAndStoreReviews($channel);
                $this->fetchAndStorePosts($channel);
                $this->fetchAndStoreQuestions($channel);
            } catch(\Exception $e) {
                 Log::error("GMB Auto-fetch failed for channel {$channel->id}: " . $e->getMessage());
            }

            $count++;
        }

        session()->forget('gmb_connect_data');

        return redirect()->route('channels.index')->with('success', "Connected {$count} Business Location(s).");
    }

    /**
     * Manual refresh of GMB data
     */
    public function refreshData(Request $request, $channelId)
    {
        try {
            $channel = Channel::findOrFail($channelId);
            
            if ($channel->type !== 'google_business') {
                return response()->json(['error' => 'Invalid channel type'], 400);
            }

            $this->ensureValidToken($channel);
            
            // Fetch and store all data types
            $this->fetchAndStoreInsights($channel);
            $this->fetchAndStoreReviews($channel);
            $this->fetchAndStorePosts($channel);
            $this->fetchAndStoreQuestions($channel);

            return response()->json([
                'success' => true,
                'message' => 'Data refreshed successfully',
                'updated_at' => now()->toISOString()
            ]);

        } catch (Exception $e) {
            Log::error('GMB Refresh Error', ['channel_id' => $channelId, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to refresh data'], 500);
        }
    }

    /**
     * Get insights data
     */
    public function getInsights($channelId)
    {
        $channel = Channel::findOrFail($channelId);
        
        $insights = \App\Models\GmbInsight::where('channel_id', $channelId)
            ->orderBy('metric_date', 'desc')
            ->limit(90)
            ->get();

        return response()->json([
            'insights' => $insights,
            'summary' => [
                'total_views' => $insights->sum('views_total'),
                'total_actions' => $insights->sum(function($i) {
                    return $i->actions_website + $i->actions_phone + $i->actions_driving_directions;
                }),
                'avg_daily_views' => $insights->avg('views_total'),
            ]
        ]);
    }

    /**
     * Get reviews
     */
    public function getReviews($channelId)
    {
        $channel = Channel::findOrFail($channelId);
        
        $reviews = \App\Models\GmbReview::where('channel_id', $channelId)
            ->orderBy('create_time', 'desc')
            ->get();

        $avgRating = $reviews->avg('star_rating');
        $ratingDistribution = $reviews->groupBy('star_rating')
            ->map(fn($group) => $group->count());

        return response()->json([
            'reviews' => $reviews,
            'summary' => [
                'total_reviews' => $reviews->count(),
                'average_rating' => round($avgRating, 2),
                'rating_distribution' => $ratingDistribution,
                'unanswered_count' => $reviews->where('review_reply', null)->count(),
            ]
        ]);
    }

    /**
     * Get posts
     */
    public function getPosts($channelId)
    {
        $channel = Channel::findOrFail($channelId);
        
        $posts = \App\Models\GmbPost::where('channel_id', $channelId)
            ->orderBy('create_time', 'desc')
            ->get();

        return response()->json([
            'posts' => $posts,
            'summary' => [
                'total_posts' => $posts->count(),
                'live_posts' => $posts->where('state', 'LIVE')->count(),
                'expired_posts' => $posts->where('state', 'EXPIRED')->count(),
            ]
        ]);
    }

    /**
     * Get questions
     */
    public function getQuestions($channelId)
    {
        $channel = Channel::findOrFail($channelId);
        
        $questions = \App\Models\GmbQuestion::where('channel_id', $channelId)
            ->orderBy('create_time', 'desc')
            ->get();

        return response()->json([
            'questions' => $questions,
            'summary' => [
                'total_questions' => $questions->count(),
                'unanswered_count' => $questions->whereNull('answer_text')->count(),
            ]
        ]);
    }

    /**
     * Fetch and store insights from Google API
     */
    private function fetchAndStoreInsights($channel)
    {
        $accessToken = $channel->config['access_token'];
        $locationName = $channel->config['location_name'];
        
        Log::info('GMB Fetching Insights', [
            'channel_id' => $channel->id,
            'location_name' => $locationName
        ]);
        
        // Fetch last 90 days of insights
        $startDate = now()->subDays(90)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        try {
            // Using the Business Profile Performance API - fetchMultiDailyMetricsTimeSeries
            // Note: This is a GET request with query parameters, not POST with JSON body
            $baseUrl = "https://businessprofileperformance.googleapis.com/v1/{$locationName}:fetchMultiDailyMetricsTimeSeries";
            
            // Build query string manually for repeated parameters
            $metrics = [
                'BUSINESS_IMPRESSIONS_DESKTOP_MAPS',
                'BUSINESS_IMPRESSIONS_DESKTOP_SEARCH',
                'BUSINESS_IMPRESSIONS_MOBILE_MAPS',
                'BUSINESS_IMPRESSIONS_MOBILE_SEARCH',
                'CALL_CLICKS',
                'WEBSITE_CLICKS',
                'BUSINESS_DIRECTION_REQUESTS',
            ];
            
            $queryParts = [];
            foreach ($metrics as $metric) {
                $queryParts[] = 'dailyMetrics=' . urlencode($metric);
            }
            
            $queryParts[] = 'dailyRange.startDate.year=' . (int)date('Y', strtotime($startDate));
            $queryParts[] = 'dailyRange.startDate.month=' . (int)date('m', strtotime($startDate));
            $queryParts[] = 'dailyRange.startDate.day=' . (int)date('d', strtotime($startDate));
            $queryParts[] = 'dailyRange.endDate.year=' . (int)date('Y', strtotime($endDate));
            $queryParts[] = 'dailyRange.endDate.month=' . (int)date('m', strtotime($endDate));
            $queryParts[] = 'dailyRange.endDate.day=' . (int)date('d', strtotime($endDate));
            
            $url = $baseUrl . '?' . implode('&', $queryParts);
            
            Log::info('GMB Insights API Request', [
                'channel_id' => $channel->id,
                'url' => $url
            ]);
            
            $response = Http::withToken($accessToken)->get($url);

            Log::info('GMB Insights API Response', [
                'channel_id' => $channel->id,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('GMB Insights Response Count', [
                    'channel_id' => $channel->id,
                    'multi_series_count' => count($data['multiDailyMetricTimeSeries'] ?? [])
                ]);
                
                // Process the time series data
                $metricsByDate = [];
                
                // The response has multiDailyMetricTimeSeries which contains dailyMetricTimeSeries array
                foreach ($data['multiDailyMetricTimeSeries'] ?? [] as $multiSeries) {
                    foreach ($multiSeries['dailyMetricTimeSeries'] ?? [] as $timeSeries) {
                        $metricType = $timeSeries['dailyMetric'] ?? null;
                        
                        foreach ($timeSeries['timeSeries']['datedValues'] ?? [] as $datedValue) {
                            $date = sprintf(
                                '%04d-%02d-%02d',
                                $datedValue['date']['year'],
                                $datedValue['date']['month'],
                                $datedValue['date']['day']
                            );
                            
                            if (!isset($metricsByDate[$date])) {
                                $metricsByDate[$date] = [
                                    'views_maps' => 0,
                                    'views_search' => 0,
                                    'actions_website' => 0,
                                    'actions_phone' => 0,
                                    'actions_driving_directions' => 0,
                                    'actions_bookings' => 0,
                                ];
                            }
                            
                            $value = $datedValue['value'] ?? 0;
                            
                            // Map metrics to database columns
                            switch ($metricType) {
                                case 'BUSINESS_IMPRESSIONS_DESKTOP_MAPS':
                                case 'BUSINESS_IMPRESSIONS_MOBILE_MAPS':
                                    $metricsByDate[$date]['views_maps'] += $value;
                                    break;
                                case 'BUSINESS_IMPRESSIONS_DESKTOP_SEARCH':
                                case 'BUSINESS_IMPRESSIONS_MOBILE_SEARCH':
                                    $metricsByDate[$date]['views_search'] += $value;
                                    break;
                                case 'WEBSITE_CLICKS':
                                    $metricsByDate[$date]['actions_website'] = $value;
                                    break;
                                case 'CALL_CLICKS':
                                    $metricsByDate[$date]['actions_phone'] = $value;
                                    break;
                                case 'BUSINESS_DIRECTION_REQUESTS':
                                    $metricsByDate[$date]['actions_driving_directions'] = $value;
                                    break;
                            }
                        }
                    }
                }
                
                // Store aggregated metrics
                foreach ($metricsByDate as $date => $metrics) {
                    \App\Models\GmbInsight::updateOrCreate(
                        [
                            'channel_id' => $channel->id,
                            'metric_date' => $date
                        ],
                        [
                            'views_maps' => $metrics['views_maps'],
                            'views_search' => $metrics['views_search'],
                            'views_total' => $metrics['views_maps'] + $metrics['views_search'],
                            'actions_website' => $metrics['actions_website'],
                            'actions_phone' => $metrics['actions_phone'],
                            'actions_driving_directions' => $metrics['actions_driving_directions'],
                            'actions_bookings' => $metrics['actions_bookings'] ?? 0,
                        ]
                    );
                }
                
                Log::info('GMB Insights Stored', [
                    'channel_id' => $channel->id,
                    'dates_count' => count($metricsByDate)
                ]);
            } else {
                Log::error('GMB Insights API Failed', [
                    'channel_id' => $channel->id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

        } catch (Exception $e) {
            Log::error('GMB Insights Fetch Error', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Fetch and store reviews from Google API
     */
    private function fetchAndStoreReviews($channel)
    {
        $accessToken = $channel->config['access_token'];
        $accountName = $channel->config['account_name'];
        $locationName = $channel->config['location_name'];

        Log::info('GMB Fetching Reviews', [
            'channel_id' => $channel->id,
            'account_name' => $accountName,
            'location_name' => $locationName
        ]);

        try {
            $response = Http::withToken($accessToken)->get(
                "https://mybusiness.googleapis.com/v4/{$accountName}/{$locationName}/reviews"
            );

            Log::info('GMB Reviews API Response', [
                'channel_id' => $channel->id,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                $reviews = $response->json()['reviews'] ?? [];
                
                Log::info('GMB Reviews Count', [
                    'channel_id' => $channel->id,
                    'count' => count($reviews)
                ]);
                
                foreach ($reviews as $review) {
                    // Convert star rating from string (FIVE, FOUR, etc.) to integer
                    $starRating = 0;
                    if (isset($review['starRating'])) {
                        $ratingMap = [
                            'FIVE' => 5,
                            'FOUR' => 4,
                            'THREE' => 3,
                            'TWO' => 2,
                            'ONE' => 1,
                        ];
                        // Robust uppercase check
                        $val = strtoupper((string)$review['starRating']);
                        $starRating = $ratingMap[$val] ?? (is_numeric($review['starRating']) ? (int)$review['starRating'] : 0);
                    }
                    
                    // Check if this is a new review (doesn't exist in database)
                    $existingReview = \App\Models\GmbReview::where('review_id', $review['reviewId'])->first();
                    $isNewReview = !$existingReview;
                    
                    // Capture reviewer email if available (usually not provided by GMB API)
                    $reviewerEmail = $review['reviewer']['email'] ?? null;
                    
                    $gmbReview = \App\Models\GmbReview::updateOrCreate(
                        ['review_id' => $review['reviewId']],
                        [
                            'channel_id' => $channel->id,
                            'reviewer_display_name' => $review['reviewer']['displayName'] ?? null,
                            'reviewer_profile_photo_url' => $review['reviewer']['profilePhotoUrl'] ?? null,
                            'star_rating' => $starRating,
                            'comment' => $review['comment'] ?? null,
                            'review_reply' => $review['reviewReply']['comment'] ?? null,
                            'reply_update_time' => isset($review['reviewReply']['updateTime']) ? $review['reviewReply']['updateTime'] : null,
                            'create_time' => $review['createTime'] ?? null,
                            'update_time' => $review['updateTime'] ?? null,
                        ]
                    );
                    
                    // Sync to inbox (creates conversation, contact, messages)
                    \App\Http\Controllers\GmbInboxHelper::syncReviewToInbox($gmbReview, $channel, $reviewerEmail, $isNewReview);
                }
            } else {
                Log::error('GMB Reviews API Failed', [
                    'channel_id' => $channel->id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

        } catch (Exception $e) {
            Log::error('GMB Reviews Fetch Error', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Fetch and store posts from Google API
     */
    private function fetchAndStorePosts($channel)
    {
        $accessToken = $channel->config['access_token'];
        $accountName = $channel->config['account_name'];
        $locationName = $channel->config['location_name'];

        Log::info('GMB Fetching Posts', [
            'channel_id' => $channel->id,
            'account_name' => $accountName,
            'location_name' => $locationName
        ]);

        try {
            $response = Http::withToken($accessToken)->get(
                "https://mybusiness.googleapis.com/v4/{$accountName}/{$locationName}/localPosts"
            );

            Log::info('GMB Posts API Response', [
                'channel_id' => $channel->id,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                $posts = $response->json()['localPosts'] ?? [];
                
                Log::info('GMB Posts Count', [
                    'channel_id' => $channel->id,
                    'count' => count($posts)
                ]);
                
                foreach ($posts as $post) {
                    \App\Models\GmbPost::updateOrCreate(
                        ['post_id' => $post['name']],
                        [
                            'channel_id' => $channel->id,
                            'summary' => $post['summary'] ?? null,
                            'call_to_action_type' => $post['callToAction']['actionType'] ?? null,
                            'call_to_action_url' => $post['callToAction']['url'] ?? null,
                            'media_format' => $post['media'][0]['mediaFormat'] ?? null,
                            'media_url' => $post['media'][0]['googleUrl'] ?? null, // Fixed: use googleUrl
                            'topic_type' => $post['topicType'] ?? 'STANDARD',
                            'event_title' => $post['event']['title'] ?? null,
                            'event_schedule' => isset($post['event']['schedule']) ? $post['event']['schedule'] : null,
                            'state' => $post['state'] ?? 'LIVE',
                            'search_url' => $post['searchUrl'] ?? null,
                            'create_time' => $post['createTime'] ?? null,
                            'update_time' => $post['updateTime'] ?? null,
                        ]
                    );
                }
            } else {
                Log::error('GMB Posts API Failed', [
                    'channel_id' => $channel->id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

        } catch (Exception $e) {
            Log::error('GMB Posts Fetch Error', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Fetch and store Q&A from Google API
     */
    private function fetchAndStoreQuestions($channel)
    {
        $accessToken = $channel->config['access_token'];
        $locationName = $channel->config['location_name'];

        Log::info('GMB Fetching Questions', [
            'channel_id' => $channel->id,
            'location_name' => $locationName
        ]);

        try {
            $response = Http::withToken($accessToken)->get(
                "https://mybusiness.googleapis.com/v4/{$locationName}/questions"
            );

            Log::info('GMB Questions API Response', [
                'channel_id' => $channel->id,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                $questions = $response->json()['questions'] ?? [];
                
                Log::info('GMB Questions Count', [
                    'channel_id' => $channel->id,
                    'count' => count($questions)
                ]);
                
                foreach ($questions as $question) {
                    $answer = $question['topAnswers'][0] ?? null;
                    
                    \App\Models\GmbQuestion::updateOrCreate(
                        ['question_id' => $question['name']],
                        [
                            'channel_id' => $channel->id,
                            'author_display_name' => $question['author']['displayName'] ?? null,
                            'author_profile_photo_url' => $question['author']['profilePhotoUrl'] ?? null,
                            'text' => $question['text'],
                            'upvote_count' => $question['upvoteCount'] ?? 0,
                            'answer_text' => $answer['text'] ?? null,
                            'answer_author_display_name' => $answer['author']['displayName'] ?? null,
                            'answer_create_time' => $answer['createTime'] ?? null,
                            'create_time' => $question['createTime'] ?? null,
                            'update_time' => $question['updateTime'] ?? null,
                        ]
                    );
                }
            } elseif ($response->status() === 404) {
                // 404 is normal - Q&A feature may not be enabled for this location
                Log::info('GMB Questions Not Available', [
                    'channel_id' => $channel->id,
                    'message' => 'Q&A feature not enabled for this location'
                ]);
            } else {
                Log::error('GMB Questions API Failed', [
                    'channel_id' => $channel->id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

        } catch (Exception $e) {
            Log::error('GMB Questions Fetch Error', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Ensure the access token is valid, refresh if needed
     */
    private function ensureValidToken($channel)
    {
        $expiresAt = $channel->config['expires_at'] ?? null;
        
        if (!$expiresAt || now()->isAfter($expiresAt)) {
            $this->refreshAccessToken($channel);
        }
    }

    /**
     * Refresh the access token
     */
    private function refreshAccessToken($channel)
    {
        $refreshToken = $channel->config['refresh_token'] ?? null;
        
        if (!$refreshToken) {
            throw new Exception('No refresh token available');
        }

        try {
            $response = Http::asForm()->post(self::TOKEN_URL, [
                'client_id' => config('services.google_business.client_id'),
                'client_secret' => config('services.google_business.client_secret'),
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            $data = $response->json();

            if (isset($data['access_token'])) {
                $config = $channel->config;
                $config['access_token'] = $data['access_token'];
                $config['expires_at'] = now()->addSeconds($data['expires_in'] ?? 3600)->toDateTimeString();
                
                $channel->config = $config;
                $channel->save();

                Log::info('GMB Token Refreshed', ['channel_id' => $channel->id]);
            } else {
                throw new Exception('Failed to refresh token');
            }

        } catch (Exception $e) {
            Log::error('GMB Token Refresh Error', ['channel_id' => $channel->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get review templates for a channel
     */
    public function getTemplates(Channel $channel)
    {
        $templates = \App\Models\GmbReviewTemplate::where('channel_id', $channel->id)
            ->orderBy('star_rating', 'desc')
            ->get();
        
        return response()->json($templates);
    }

    /**
     * Store a new review template
     */
    public function storeTemplate(Request $request, Channel $channel)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'star_rating' => 'required|integer|min:1|max:5',
            'instructions' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $template = \App\Models\GmbReviewTemplate::create([
            'channel_id' => $channel->id,
            ...$validated
        ]);

        return response()->json($template, 201);
    }

    /**
     * Update a review template
     */
    public function updateTemplate(Request $request, Channel $channel, $templateId)
    {
        $template = \App\Models\GmbReviewTemplate::where('channel_id', $channel->id)
            ->findOrFail($templateId);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'star_rating' => 'sometimes|integer|min:1|max:5',
            'instructions' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $template->update($validated);

        return response()->json($template);
    }

    /**
     * Delete a review template
     */
    public function destroyTemplate(Channel $channel, $templateId)
    {
        $template = \App\Models\GmbReviewTemplate::where('channel_id', $channel->id)
            ->findOrFail($templateId);

        $template->delete();

        return response()->json(['message' => 'Template deleted successfully']);
    }
}
