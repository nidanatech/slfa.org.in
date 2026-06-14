<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\StageChangeLog;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tenant = $user->tenant;

        if (!$tenant) {
            return response()->json(['error' => 'No active business selected.'], 404);
        }

        return response()->json([
            'business_pulse' => $this->getBusinessPulse($tenant),
            'smart_alerts' => $this->getSmartAlerts($tenant),
            'pipeline_snapshot' => $this->getPipelineSnapshot($tenant),
            'recent_activities' => $this->getRecentActivity($tenant, 10),
            'is_chatbot_enabled' => $tenant->settings['is_chatbot_enabled'] ?? true,
        ]);
    }

    private function getBusinessPulse($tenant)
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $newLeadsToday = $tenant->contacts()
            ->where('created_at', '>=', $today)
            ->where(function($query) {
                $query->whereJsonContains('contact_types', 'lead')
                      ->orWhereNull('contact_types');
            })->count();

        $newLeadsYesterday = $tenant->contacts()
            ->where('created_at', '>=', $yesterday)
            ->where('created_at', '<', $today)
            ->where(function($query) {
                $query->whereJsonContains('contact_types', 'lead')
                      ->orWhereNull('contact_types');
            })->count();

        $conversationsAwaitingReply = $tenant->conversations()
            ->where('status', 'open')
            ->whereHas('messages', function($q) {
                $q->where('direction', 'inbound')
                  ->where('created_at', '>', function($subQuery) {
                      $subQuery->selectRaw('COALESCE(MAX(created_at), ?)', ['1970-01-01'])
                               ->from('messages')
                               ->where('direction', 'outbound');
                  });
            })->count();

        $hotLeads = $tenant->contacts()
            ->where(function($query) {
                $query->where('rating', '>=', 4)
                      ->orWhere('stage_confidence', '>=', 0.8);
            })->count();

        return [
            'new_leads_today' => $newLeadsToday,
            'leads_trend' => $newLeadsToday - $newLeadsYesterday,
            'awaiting_reply' => $conversationsAwaitingReply,
            'hot_leads' => $hotLeads,
        ];
    }

    private function getSmartAlerts($tenant)
    {
        $alerts = [];
        $urgentLeads = $tenant->contacts()
            ->where('rating', '>=', 4)
            ->whereHas('conversations', function($q) {
                $q->where('last_message_at', '<', now()->subHours(2))
                  ->where('status', 'open');
            })->count();

        if ($urgentLeads > 0) {
            $alerts[] = [
                'type' => 'urgent',
                'title' => 'Urgent Follow-up',
                'message' => "{$urgentLeads} hot leads need attention.",
            ];
        }

        return $alerts;
    }

    private function getPipelineSnapshot($tenant)
    {
        $pipeline = $tenant->pipelines()->where('is_default', true)->first() ?: $tenant->pipelines()->first();
        
        if (!$pipeline) return null;

        $stages = $pipeline->stages()->orderBy('order_index')->get()->map(function($stage) {
            return [
                'name' => $stage->name,
                'count' => $stage->contacts()->count(),
                'color' => $stage->color ?? '#3b82f6'
            ];
        });

        return [
            'name' => $pipeline->name,
            'stages' => $stages,
            'total_value' => (float) ($pipeline->opportunities()->sum('value_amount') ?? 0),
        ];
    }

    private function getRecentActivity($tenant, $limit = 10)
    {
        $stageChanges = StageChangeLog::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', now()->subDay())
            ->with(['contact', 'newStage'])
            ->latest()
            ->take($limit)
            ->get()
            ->map(function($log) {
                return [
                    'type' => 'stage_change',
                    'message' => ($log->contact?->name ?? 'Contact') . " moved to " . ($log->newStage?->name ?? 'Next Stage'),
                    'time' => $log->created_at->diffForHumans(),
                ];
            });

        return $stageChanges;
    }
}
