<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Rookie',
                'slug' => 'rookie',
                'description' => 'Every hero starts somewhere. Best for solo founders.',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'stripe_price_id' => 'price_rookie_free_placeholder',
                'features' => [
                    '1 User',
                    '1 AI Bot',
                    '10 Conversations / day',
                    'Basic Tokens',
                    '1 Pipeline',
                    'Standard Widgets'
                ],
                'resource_limits' => [
                    'users' => 1,
                    'bots' => 1,
                    'conversations_monthly' => 300,
                    'tokens_monthly' => 10000,
                    'posts_monthly' => 0,
                    'pipelines' => 1,
                    'seo_optimization' => false,
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Trusted',
                'slug' => 'trusted',
                'description' => 'Your hero’s right hand. Best for early-stage teams.',
                'price_monthly' => 49,
                'price_yearly' => 490,
                'stripe_price_id' => 'price_trusted_monthly_placeholder',
                'features' => [
                    '3 Users',
                    '3 AI Bots',
                    '1,000 Conversations / mo',
                    'Medium Tokens',
                    '3 Pipelines',
                    'SEO Optimized Posts',
                    'Social Channels'
                ],
                'resource_limits' => [
                    'users' => 3,
                    'bots' => 3,
                    'conversations_monthly' => 1000,
                    'tokens_monthly' => 100000,
                    'posts_monthly' => 50,
                    'pipelines' => 3,
                    'seo_optimization' => true,
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Elite',
                'slug' => 'elite',
                'description' => 'A force multiplier for growing teams and agencies.',
                'price_monthly' => 149,
                'price_yearly' => 1490,
                'stripe_price_id' => 'price_elite_monthly_placeholder',
                'features' => [
                    '10 Users',
                    '10 AI Bots',
                    '10,000 Conversations / mo',
                    'High Tokens',
                    '10 Pipelines',
                    'AI Meta Generation',
                    'Google Business'
                ],
                'resource_limits' => [
                    'users' => 10,
                    'bots' => 10,
                    'conversations_monthly' => 10000,
                    'tokens_monthly' => 500000,
                    'posts_monthly' => 300,
                    'pipelines' => 10,
                    'seo_optimization' => true,
                    'ai_meta_generation' => true,
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Co-Hero',
                'slug' => 'co-hero',
                'description' => 'Fights alongside you. Best for enterprises.',
                'price_monthly' => 499,
                'price_yearly' => 4990,
                'stripe_price_id' => 'price_cohero_monthly_placeholder',
                'features' => [
                    'Unlimited Users',
                    'Unlimited AI Bots',
                    'Unlimited Conversations',
                    'Dedicated Tokens',
                    'Unlimited Pipelines',
                    'Enterprise SLA',
                    'Custom Solutions'
                ],
                'resource_limits' => [
                    'users' => -1,
                    'bots' => -1,
                    'conversations_monthly' => -1,
                    'tokens_monthly' => -1,
                    'posts_monthly' => -1,
                    'pipelines' => -1,
                    'seo_optimization' => true,
                    'ai_meta_generation' => true,
                ],
                'is_active' => true,
            ],
        ];

        // Update or create plans (safe for production with existing subscriptions)
        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']], // Match by slug
                $planData // Update with all data
            );
        }
    }
}
