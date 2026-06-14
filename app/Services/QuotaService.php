<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QuotaService
{
    /**
     * Default resource limits if none are defined in plan or tenant.
     */
    public const DEFAULTS = [
        'users' => 5,
        'bots' => 2,
        'pipelines' => 1,
        'conversations_monthly' => 1000,
        'tokens_monthly' => 50000,
        'posts_monthly' => 100,
    ];

    /**
     * Resolve the limit for a specific resource for a tenant.
     */
    public function getLimit(Tenant $tenant, string $resource): int
    {
        // 1. Check Tenant-specific override
        $tenantLimits = $tenant->resource_limits ?? [];
        if (isset($tenantLimits[$resource])) {
            return (int) $tenantLimits[$resource];
        }

        // 2. Check Plan-defined limit
        $plan = $tenant->subscriptions()->active()->first()?->plan;
        if (!$plan) {
            // If no plan, check if they have a "main" subscription that might have a plan
            $plan = $tenant->plan; // Assuming a direct relation or helper exists
        }

        if ($plan && isset($plan->resource_limits[$resource])) {
            return (int) $plan->resource_limits[$resource];
        }

        // 3. System Default
        return self::DEFAULTS[$resource] ?? -1; // -1 means unlimited or undefined
    }

    /**
     * Calculate the current usage for a specific resource for a tenant.
     */
    public function getUsage(Tenant $tenant, string $resource): int
    {
        switch ($resource) {
            case 'users':
                return $tenant->users()->count();

            case 'bots':
                return $tenant->bots()->count();

            case 'pipelines':
                return DB::table('pipelines')->where('tenant_id', $tenant->id)->count();

            case 'conversations_monthly':
                return $tenant->conversations()
                    ->where('created_at', '>=', Carbon::now()->startOfMonth())
                    ->count();

            case 'tokens_monthly':
                return (int) DB::table('tenant_daily_token_usage')
                    ->where('tenant_id', $tenant->id)
                    ->where('date', '>=', Carbon::now()->startOfMonth()->toDateString())
                    ->sum('total_tokens');

            case 'posts_monthly':
                return DB::table('social_posts')
                    ->where('tenant_id', $tenant->id)
                    ->where('created_at', '>=', Carbon::now()->startOfMonth())
                    ->count();

            default:
                return 0;
        }
    }

    /**
     * Check if a tenant can create/add more of a specific resource.
     */
    public function canCreate(Tenant $tenant, string $resource): bool
    {
        $limit = $this->getLimit($tenant, $resource);

        // -1 or 0 might mean unlimited depending on convention, but let's say -1 is unlimited
        if ($limit === -1) {
            return true;
        }

        $usage = $this->getUsage($tenant, $resource);

        return $usage < $limit;
    }

    /**
     * Get all limits and usage for a tenant (for dashboard display).
     */
    public function getQuotaStatus(Tenant $tenant): array
    {
        $resources = array_keys(self::DEFAULTS);
        $status = [];

        foreach ($resources as $resource) {
            $status[$resource] = [
                'limit' => $this->getLimit($tenant, $resource),
                'usage' => $this->getUsage($tenant, $resource),
            ];
        }

        return $status;
    }
}
