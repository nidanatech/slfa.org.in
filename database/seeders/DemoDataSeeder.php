<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\Post;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Plans
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small businesses starting their automation journey.',
                'price_monthly' => 29.00,
                'price_yearly' => 290.00,
                'stripe_price_id' => 'price_starter_monthly_test',
                'features' => ['3 Channels', '1,000 Messages/mo', 'Basic AI Bot', 'Email Support'],
                'is_active' => true,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Advanced features for growing teams.',
                'price_monthly' => 99.00,
                'price_yearly' => 990.00,
                'stripe_price_id' => 'price_pro_monthly_test',
                'features' => ['Unlimited Channels', '10,000 Messages/mo', 'Advanced AI Bot', 'Priority Support', 'Custom Branding'],
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Custom solutions for large organizations.',
                'price_monthly' => 299.00,
                'price_yearly' => 2990.00,
                'stripe_price_id' => 'price_enterprise_monthly_test',
                'features' => ['Custom Limits', 'Dedicated Support', 'White-labeling', 'API Access', 'On-premise Options'],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(['slug' => $planData['slug']], $planData);
        }

        // 2. Seed CMS Posts
        $admin = User::where('email', 'admin@admin.com')->first();
        if ($admin) {
            $posts = [
                [
                    'title' => 'Unleashing the Power of AI in Omnichannel Communication',
                    'slug' => 'power-of-ai-omnichannel',
                    'content' => '<h1>The Future is AI</h1><p>AI is transforming how we talk to customers. By integrating LLMs into your communication stack, you can provide 24/7 support without increasing headcount.</p><h2>Key Benefits</h2><ul><li>Instant Response</li><li>Scalability</li><li>Cost Reduction</li></ul>',
                    'excerpt' => 'How AI is changing the landscape of customer engagement across all channels.',
                    'type' => 'blog',
                    'category' => 'Technology',
                    'status' => 'published',
                    'author_id' => $admin->id,
                    'published_at' => now(),
                ],
                [
                    'title' => 'Integrating WhatsApp Business API: A Complete Guide',
                    'slug' => 'whatsapp-business-guide',
                    'content' => '<h1>WhatsApp Integration Guide</h1><p>Step by step guide to connecting WhatsApp Business API to your dashboard.</p><ol><li>Verify Business</li><li>Setup App</li><li>Connect Webhooks</li></ol>',
                    'excerpt' => 'Everything you need to know about setting up WhatsApp for your business.',
                    'type' => 'resource',
                    'category' => 'Guides',
                    'status' => 'published',
                    'author_id' => $admin->id,
                    'published_at' => now()->subDays(2),
                ],
            ];

            foreach ($posts as $postData) {
                Post::updateOrCreate(['slug' => $postData['slug']], $postData);
            }
        }

        // 3. Seed Support Tickets
        $tenant = Tenant::first();
        $user = User::where('is_system_user', false)->first();

        if ($tenant && $user) {
            $ticket1 = SupportTicket::updateOrCreate(['subject' => 'Billing error on Pro plan'], [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'subject' => 'Billing error on Pro plan',
                'category' => 'Billing',
                'priority' => 'high',
                'status' => 'open',
            ]);

            $ticket1->messages()->updateOrCreate(['message' => 'I was charged twice this month.'], [
                'user_id' => $user->id,
                'message' => 'I was charged twice this month. Please investigate and refund.',
                'is_internal' => false
            ]);

            $ticket2 = SupportTicket::updateOrCreate(['subject' => 'WhatsApp channel disconnection'], [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'subject' => 'WhatsApp channel disconnection',
                'category' => 'Technical',
                'priority' => 'urgent',
                'status' => 'in_progress',
                'assigned_to' => $admin->id ?? null,
            ]);

            $ticket2->messages()->updateOrCreate(['message' => 'My WhatsApp channel keeps going offline.'], [
                'user_id' => $user->id,
                'message' => 'My WhatsApp channel keeps going offline every 2 hours.',
                'is_internal' => false
            ]);
            
            if ($admin) {
                $ticket2->messages()->updateOrCreate(['message' => 'I am looking into the webhook logs.'], [
                    'user_id' => $admin->id,
                    'message' => 'I am looking into the webhook logs to see why the session is expiring.',
                    'is_internal' => true
                ]);
            }
        }
    }
}
