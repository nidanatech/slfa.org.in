<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\SupportTicket;
use App\Models\Subscription;
use App\Models\Plan;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => $this->getStats(),
            'chart_data' => $this->getChartData(),
            'alerts' => $this->getAlerts(),
        ]);
    }

    private function getStats()
    {
        // Total Tenants
        $totalTenants = Tenant::count();
        $lastMonthTenants = Tenant::where('created_at', '<', Carbon::now()->startOfMonth())->count();
        $tenantChange = $lastMonthTenants > 0 ? (($totalTenants - $lastMonthTenants) / $lastMonthTenants) * 100 : 0;

        // Open Tickets
        $openTickets = SupportTicket::where('status', 'open')->count();
        $lastMonthTickets = SupportTicket::where('status', 'open')
            ->where('created_at', '<', Carbon::now()->startOfMonth())
            ->count();
        $ticketChange = $lastMonthTickets > 0 ? (($openTickets - $lastMonthTickets) / $lastMonthTickets) * 100 : 0;

        // Active Subscriptions
        $activeSubscriptions = Subscription::where('stripe_status', 'active')->count();

        // Total Revenue (MRR Approximation)
        // Sum up the prices of plans for all active subscriptions
        $totalRevenue = DB::table('subscriptions')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.stripe_status', 'active')
            ->sum('plans.price_monthly');

        return [
            'total_tenants' => $totalTenants,
            'tenant_change' => round($tenantChange, 1) . '%',
            'tenant_trend' => $tenantChange >= 0 ? 'up' : 'down',
            
            'open_tickets' => $openTickets,
            'ticket_change' => round($ticketChange, 1) . '%',
            'ticket_trend' => $ticketChange <= 0 ? 'down' : 'up', // Down is good for tickets
            
            'active_subscriptions' => $activeSubscriptions,
            
            'total_revenue' => (float)$totalRevenue,
            'revenue_change' => '+15.2%', // Mocked for now until we have history
            'revenue_trend' => 'up',
        ];
    }

    private function getChartData()
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthName = $month->format('M');
            
            $tenantsCount = Tenant::whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();
                
            // Revenue for that month (simplified: count of active subs * avg price)
            // Ideally should check historical subscription status
            $revenue = DB::table('subscriptions')
                ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
                ->whereMonth('subscriptions.created_at', '<=', $month->month)
                ->whereYear('subscriptions.created_at', '<=', $month->year)
                ->where(function($query) use ($month) {
                    $query->whereNull('subscriptions.ends_at')
                          ->orWhere('subscriptions.ends_at', '>', $month->endOfMonth());
                })
                ->sum('plans.price_monthly');

            $data[] = [
                'name' => $monthName,
                'tenants' => $tenantsCount,
                'revenue' => (float)$revenue,
            ];
        }
        return $data;
    }

    private function getAlerts()
    {
        $alerts = [];

        // Critical: High latency spike (Mocked as we don't have monitoring logs yet)
        // $alerts[] = [
        //     'id' => 1,
        //     'type' => 'critical',
        //     'msg' => 'Stripe Webhook delivery failure rate increased (5%)',
        //     'time' => '10 mins ago'
        // ];

        // Warning: Tenant storage (Mocked)
        $tenantsNearLimit = Tenant::take(2)->get(); // Example
        foreach ($tenantsNearLimit as $tenant) {
            $alerts[] = [
                'id' => uniqid(),
                'type' => 'warning',
                'msg' => "Tenant \"{$tenant->name}\" storage threshold reached 85%",
                'time' => '1 hour ago'
            ];
        }

        // Info: New Support Ticket
        $newTickets = SupportTicket::with('tenant')->latest()->take(2)->get();
        foreach ($newTickets as $ticket) {
            $alerts[] = [
                'id' => uniqid(),
                'type' => 'info',
                'msg' => "New support ticket #{$ticket->id} from " . ($ticket->tenant->name ?? 'Unknown'),
                'time' => $ticket->created_at->diffForHumans()
            ];
        }

        // Success: New Subscriptions
        $newSubs = Subscription::with('tenant')->latest()->take(2)->get();
        foreach ($newSubs as $sub) {
            $alerts[] = [
                'id' => uniqid(),
                'type' => 'success',
                'msg' => "New subscription activated for tenant \"" . ($sub->tenant->name ?? 'Unknown') . "\"",
                'time' => $sub->created_at->diffForHumans()
            ];
        }

        return array_slice($alerts, 0, 5);
    }
}
