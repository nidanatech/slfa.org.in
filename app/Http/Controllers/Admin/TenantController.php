<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Services\DashboardAssistantService;

class TenantController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => Tenant::with(['users', 'subscriptions.plan'])->latest()->get(),
            'plans' => Plan::all()
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Tenants/Create', [
            'plans' => Plan::all()
        ]);
    }

    public function store(Request $request, \App\Services\DashboardAssistantService $service)
    {
        $request->validate([
            'tenant_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'plan_id' => 'required|exists:plans,id',
        ]);

        try {
            DB::beginTransaction();

            // Create Tenant
            $tenant = Tenant::create([
                'name' => $request->tenant_name,
                'is_active' => true,
                'settings' => [
                    'modules' => [
                        'marketing' => ['enabled' => true],
                        'accounting' => ['enabled' => true],
                    ],
                ]
            ]);

            // Create Owner User
            $user = User::create([
                'name' => $request->owner_name,
                'email' => $request->owner_email,
                'password' => Hash::make($request->password),
                'tenant_id' => $tenant->id,
                'account_status' => 'active',
            ]);

            // Attach Subscription/Plan (Manual provisioning)
            $plan = Plan::find($request->plan_id);
            $tenant->subscriptions()->create([
                'plan_id' => $plan->id,
                'status' => 'active',
                'type' => 'default',
                'name' => 'main',
                'stripe_id' => 'manual_' . uniqid(),
                'stripe_status' => 'active',
                'stripe_price' => 'manual',
                'quantity' => 1,
            ]);

            DB::commit();

            // Initialize Dashboard Assistant (Best effort)
            try {
                $service->ensureAssistant($tenant);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Could not initialize dashboard assistant for {$user->email}: " . $e->getMessage());
            }

            return redirect()->route('admin.tenants.index')->with('success', 'Tenant provisioned successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to provision tenant: ' . $e->getMessage());
        }
    }

    public function show(Tenant $tenant)
    {
        // Load relationships
        $tenant->load(['users', 'subscriptions.plan', 'channels', 'conversations']);

        // Get statistics
        $stats = [
            'total_channels' => $tenant->channels()->count(),
            'active_channels' => $tenant->channels()->where('is_active', true)->count(),
            'total_conversations' => $tenant->conversations()->count(),
            'total_contacts' => $tenant->contacts()->count(),
            'total_users' => $tenant->users()->count(),
        ];

        // Get AI usage (last 30 days)
        try {
            $aiUsage = DB::table('tenant_daily_token_usage')
                ->where('tenant_id', $tenant->id)
                ->where('date', '>=', now()->subDays(30))
                ->sum('total_tokens');
        } catch (\Exception $e) {
            // Table doesn't exist yet, default to 0
            $aiUsage = 0;
        }

        // Get Quota Status
        $quotas = app(\App\Services\QuotaService::class)->getQuotaStatus($tenant);

        // Get recent channels
        $recentChannels = $tenant->channels()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Get channel breakdown by type
        $channelsByType = $tenant->channels()
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get();

        return Inertia::render('Admin/Tenants/Show', [
            'tenant' => $tenant,
            'stats' => $stats,
            'aiUsage' => $aiUsage,
            'quotas' => $quotas,
            'recentChannels' => $recentChannels,
            'channelsByType' => $channelsByType,
        ]);
    }

    public function updateApiKey(Request $request, Tenant $tenant)
    {
        $request->validate([
            'openai_key' => 'required|string|min:20',
        ]);

        $tenant->update([
            'openai_key' => $request->openai_key,
        ]);

        return back()->with('success', 'OpenAI API key updated successfully!');
    }

    public function updateResourceLimits(Request $request, Tenant $tenant)
    {
        $limits = $request->validate([
            'users' => 'nullable|integer|min:-1',
            'bots' => 'nullable|integer|min:-1',
            'pipelines' => 'nullable|integer|min:-1',
            'conversations_monthly' => 'nullable|integer|min:-1',
            'tokens_monthly' => 'nullable|integer|min:-1',
            'posts_monthly' => 'nullable|integer|min:-1',
        ]);

        // Filter out null values to use overrides only where specified
        $limits = array_filter($limits, fn($v) => !is_null($v));

        $tenant->update([
            'resource_limits' => $limits,
        ]);

        return back()->with('success', 'Resource limits updated successfully!');
    }

    public function destroy(Tenant $tenant)
    {
        try {
            DB::beginTransaction();

            // Soft delete the business profile if it exists
            $tenant->businessProfile()->delete();

            // Soft delete the tenant
            $tenant->delete();

            DB::commit();

            return redirect()->route('admin.tenants.index')->with('success', 'Tenant and business profile soft deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete tenant: ' . $e->getMessage());
        }
    }

    public function syncAssistant(Tenant $tenant, DashboardAssistantService $service)
    {
        try {
            $service->syncAssistant($tenant);
            return back()->with('success', 'Dashboard Assistant parameters synchronized successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to synchronize assistant: ' . $e->getMessage());
        }
    }

    public function syncBots(Tenant $tenant)
    {
        try {
            $bots = $tenant->aiBots()->get();
            
            if ($bots->isEmpty()) {
                return back()->with('info', 'No AI bots found for this tenant.');
            }
            
            $syncedCount = 0;
            $failedCount = 0;
            $errors = [];
            
            foreach ($bots as $bot) {
                try {
                    $bot->syncOpenAiAssistant(false);
                    $syncedCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Bot '{$bot->name}': " . $e->getMessage();
                    \Illuminate\Support\Facades\Log::error("Failed to sync bot {$bot->id}: " . $e->getMessage());
                }
            }
            
            if ($failedCount === 0) {
                return back()->with('success', "Successfully synced {$syncedCount} AI bot(s)!");
            } elseif ($syncedCount === 0) {
                return back()->with('error', "Failed to sync all bots. " . implode('; ', $errors));
            } else {
                return back()->with('warning', "Partially synced: {$syncedCount} succeeded, {$failedCount} failed. Errors: " . implode('; ', array_slice($errors, 0, 3)));
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to sync bots: ' . $e->getMessage());
        }
    }
}
