<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Traits\BelongsToTenant;
use App\Traits\HasPublicId;

class Opportunity extends Model
{
    use BelongsToTenant, HasPublicId;
    protected $fillable = [
        'tenant_id',
        'name',
        'pipeline_id',
        'stage_id',
        'contact_id',
        'company_id',
        'owner_id',
        'value_amount',
        'currency',
        'expected_close_date',
        'next_followup_date',
        'status',
        'source',
        'closed_at',
        'payment_status',
        'stripe_session_id',
        'product_id',
        'product_variation_id',
        'portal_token',
    ];

    protected $appends = ['public_id'];

    protected $casts = [
        'value_amount' => 'decimal:2',
        'expected_close_date' => 'date',
        'next_followup_date' => 'date',
        'closed_at' => 'datetime',
    ];

    // Relationships
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'stage_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function stageHistory(): HasMany
    {
        return $this->hasMany(OpportunityStageHistory::class)->orderBy('changed_at', 'desc');
    }

    public function customFieldValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'object', 'object_type', 'object_id');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'object', 'taggables', 'object_id', 'tag_id');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'object', 'object_type', 'object_id');
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'object', 'object_type', 'object_id');
    }

    public function charter()
    {
        return $this->hasOne(OpportunityCharter::class)->where('is_current', true);
    }

    public function charterVersions(): HasMany
    {
        return $this->hasMany(OpportunityCharter::class)->orderBy('version_number', 'desc');
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(ProjectChangeRequest::class)->latest();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
    public function timelineSteps(): HasMany
    {
        return $this->hasMany(ProjectTimelineStep::class, 'opportunity_id')->orderBy('order_position');
    }

    /**
     * Initialize or sync PMBOK timeline steps for this project.
     */
    public function syncTimelineSteps()
    {
        $blueprint = [
            'INITIATION' => [
                ['key' => 'req_overview', 'label' => 'Conduct requirement overview meeting with client', 'desc' => 'Initial scope alignment call', 'order' => 1],
                ['key' => 'doc_scope', 'label' => 'Document scope, goals, constraints and assumptions', 'desc' => 'High-level project boundaries', 'order' => 2],
                ['key' => 'id_stakeholders', 'label' => 'Identify key stakeholders and their roles', 'desc' => 'Who are the decision makers?', 'order' => 3],
                ['key' => 'reg_stakeholders', 'label' => 'Register stakeholders with influence/interest analysis', 'desc' => 'Stakeholder matrix registration', 'order' => 4],
                ['key' => 'draft_charter', 'label' => 'Draft project charter', 'desc' => 'Objectives, deliverables, budget, timeline', 'order' => 5],
                ['key' => 'id_risks', 'label' => 'Define high-level risks and assumptions in the charter', 'desc' => 'Initial risk assessment', 'order' => 6],
                ['key' => 'share_charter', 'label' => 'Share charter with client for review via portal', 'desc' => 'Publish draft to client view', 'order' => 7],
                ['key' => 'signed_charter', 'label' => 'Obtain digital signature / formal authorization from sponsor', 'desc' => 'Official project kick-off approval', 'order' => 8],
            ],
            'PLANNING' => [
                ['key' => 'adv_invoice', 'label' => 'Generate and send advance invoice to client', 'desc' => 'Initial financial gating', 'order' => 1],
                ['key' => 'payment_followup', 'label' => 'Follow up on payment if not received', 'desc' => 'Accounts receivable management', 'order' => 2],
                ['key' => 'payment_confirm', 'label' => 'Confirm payment received and issue receipt', 'desc' => 'Clear for kickoff', 'order' => 3],
                ['key' => 'fin_clearance', 'label' => 'Update project financials and clear for kickoff', 'desc' => 'Internal budget activation', 'order' => 4],
                ['key' => 'detailed_scope', 'label' => 'Define detailed scope statement', 'desc' => 'Granular deliverable breakdown', 'order' => 5],
                ['key' => 'create_wbs', 'label' => 'Create Work Breakdown Structure (WBS) and WBS dictionary', 'desc' => 'Decompose work segments', 'order' => 6],
                ['key' => 'seq_activities', 'label' => 'Define and sequence all project activities', 'desc' => 'Logic flow of tasks', 'order' => 7],
                ['key' => 'est_durations', 'label' => 'Estimate activity durations and assign resources', 'desc' => 'Timeline baseline', 'order' => 8],
                ['key' => 'build_schedule', 'label' => 'Build project schedule with milestones and deadlines', 'desc' => 'Gantt/Timeline finalization', 'order' => 9],
                ['key' => 'cost_baseline', 'label' => 'Develop cost baseline and project budget', 'desc' => 'Cash flow planning', 'order' => 10],
                ['key' => 'alloc_resources', 'label' => 'Estimate and allocate resources (team, tools, budget)', 'desc' => 'Logistics prep', 'order' => 11],
                ['key' => 'plan_quality', 'label' => 'Plan quality standards and quality assurance approach', 'desc' => 'QA framework', 'order' => 12],
                ['key' => 'resource_plan', 'label' => 'Develop resource management plan', 'desc' => 'Team management baseline', 'order' => 13],
                ['key' => 'mitigation_stra', 'label' => 'Identify risks and define mitigation strategies', 'desc' => 'Risk management plan', 'order' => 14],
                ['key' => 'risk_analysis', 'label' => 'Perform qualitative and quantitative risk analysis', 'desc' => 'Probability vs Impact', 'order' => 15],
                ['key' => 'plan_procure', 'label' => 'Plan procurement — identify what needs to be bought/outsourced', 'desc' => 'Vendor management prep', 'order' => 16],
                ['key' => 'comm_plan', 'label' => 'Define communication plan and reporting cadence', 'desc' => 'Stakeholder info flow', 'order' => 17],
                ['key' => 'stakeholder_strat', 'label' => 'Plan stakeholder engagement strategy', 'desc' => 'Influence management', 'order' => 18],
                ['key' => 'pmp_approval', 'label' => 'Get project management plan approved by sponsor', 'desc' => 'Final authorization to execute', 'order' => 19],
                ['key' => 'kickoff_prep', 'label' => 'Prepare kickoff meeting agenda and share with attendees', 'desc' => 'Meeting logistics', 'order' => 20],
                ['key' => 'conduct_kickoff', 'label' => 'Conduct project kickoff meeting with team and client', 'desc' => 'Official start of work', 'order' => 21],
            ],
            'EXECUTION' => [
                ['key' => 'assign_tasks', 'label' => 'Assign tasks to team members and confirm acceptance', 'desc' => 'Delegation and commitment', 'order' => 1],
                ['key' => 'setup_workspace', 'label' => 'Set up project workspace, tools, and access', 'desc' => 'Figma, Git, Jira, etc.', 'order' => 2],
                ['key' => 'exec_deliverables', 'label' => 'Execute deliverables as per schedule and WBS', 'desc' => 'Active production', 'order' => 3],
                ['key' => 'team_standups', 'label' => 'Conduct regular team stand-ups and update task status', 'desc' => 'Daily/Weekly syncs', 'order' => 4],
                ['key' => 'manage_dev_team', 'label' => 'Manage and develop team — resolve conflicts', 'desc' => 'HR/Soft skills management', 'order' => 5],
                ['key' => 'weekly_updates', 'label' => 'Send weekly progress updates to client', 'desc' => 'Reporting compliance', 'order' => 6],
                ['key' => 'review_meetings', 'label' => 'Conduct scheduled review / demo meetings with client', 'desc' => 'Visual feedback loops', 'order' => 7],
                ['key' => 'log_feedback', 'label' => 'Collect and log client feedback; update scope if needed', 'desc' => 'Adjustment management', 'order' => 8],
                ['key' => 'active_engagement', 'label' => 'Manage stakeholder engagement actively', 'desc' => 'Relationship management', 'order' => 9],
                ['key' => 'quality_audits', 'label' => 'Implement quality assurance activities and audits', 'desc' => 'Process compliance', 'order' => 10],
                ['key' => 'criteria_check', 'label' => 'Review deliverables against acceptance criteria', 'desc' => 'Internal QA', 'order' => 11],
                ['key' => 'vendor_coord', 'label' => 'Manage procurement — coordinate with vendors', 'desc' => 'Contractor management', 'order' => 12],
                ['key' => 'progress_invoices', 'label' => 'Raise milestone / progress invoices', 'desc' => 'Cash flow adherence', 'order' => 13],
                ['key' => 'implement_changes', 'label' => 'Implement approved changes to scope, schedule, or cost', 'desc' => 'CR execution', 'order' => 14],
                ['key' => 'manage_issues', 'label' => 'Track and manage risks and issues log', 'desc' => 'Problem solving', 'order' => 15],
            ],
            'MONITORING' => [
                ['key' => 'schedule_monitor', 'label' => 'Monitor schedule and flag delays vs. baseline plan', 'desc' => 'Timeline variance', 'order' => 1],
                ['key' => 'budget_track', 'label' => 'Track budget spend vs. forecast; flag overruns early', 'desc' => 'Financial variance', 'order' => 2],
                ['key' => 'status_sharing', 'label' => 'Prepare and share periodic status reports', 'desc' => 'Stakeholder visibility', 'order' => 3],
                ['key' => 'eva_metrics', 'label' => 'Perform earned value analysis (SPI, CPI)', 'desc' => 'Performance math', 'order' => 4],
                ['key' => 'scope_val', 'label' => 'Validate scope — get formal acceptance of deliverables', 'desc' => 'Closure gating', 'order' => 5],
                ['key' => 'creep_control', 'label' => 'Control scope — prevent and document scope creep', 'desc' => 'Boundary protection', 'order' => 6],
                ['key' => 'quality_watch', 'label' => 'Validate completed deliverables meet quality standards', 'desc' => 'Output QA', 'order' => 7],
                ['key' => 'resource_monitor', 'label' => 'Monitor team performance and address resource issues', 'desc' => 'Capacity monitoring', 'order' => 8],
                ['key' => 'engage_adjust', 'label' => 'Monitor stakeholder engagement and adjust strategy', 'desc' => 'Relationship health', 'order' => 9],
                ['key' => 'process_cr', 'label' => 'Review and process change requests', 'desc' => 'Change board control', 'order' => 10],
                ['key' => 'plan_updates', 'label' => 'Update project plan if approved changes affect scope', 'desc' => 'Re-baselining', 'order' => 11],
                ['key' => 'risk_review', 'label' => 'Review risk register and update mitigations', 'desc' => 'Adaptive protection', 'order' => 12],
                ['key' => 'procure_perf', 'label' => 'Monitor procurement performance and compliance', 'desc' => 'Vendor health', 'order' => 13],
                ['key' => 'escalate_pm', 'label' => 'Escalate critical issues to sponsor if unresolvable', 'desc' => 'Authority trigger', 'order' => 14],
            ],
            'CLOSING' => [
                ['key' => 'final_delivery', 'label' => 'Deliver all final deliverables to client', 'desc' => 'The big release', 'order' => 1],
                ['key' => 'final_uat', 'label' => 'Conduct final review / UAT session with client', 'desc' => 'Ultimate validation', 'order' => 2],
                ['key' => 'formal_signoff', 'label' => 'Obtain formal sign-off / acceptance letter', 'desc' => 'Legal closure marker', 'order' => 3],
                ['key' => 'close_quality', 'label' => 'Conduct final quality review and close records', 'desc' => 'QA archival', 'order' => 4],
                ['key' => 'final_bill', 'label' => 'Raise and collect final invoice / retention payment', 'desc' => 'Final financial marker', 'order' => 5],
                ['key' => 'cost_reconcile', 'label' => 'Reconcile all project costs and close records', 'desc' => 'Budget final tally', 'order' => 6],
                ['key' => 'procure_close', 'label' => 'Close all procurement contracts formally', 'desc' => 'Vendor formal ends', 'order' => 7],
                ['key' => 'vendor_ends', 'label' => 'Close any vendor or subcontractor contracts', 'desc' => 'Partner closure', 'order' => 8],
                ['key' => 'lessons_learned', 'label' => 'Conduct lessons learned session with team', 'desc' => 'Growth analysis', 'order' => 9],
                ['key' => 'store_kb', 'label' => 'Document and store lessons in organisational KB', 'desc' => 'IP building', 'order' => 10],
                ['key' => 'archive_docs', 'label' => 'Archive all project documents and notes', 'desc' => 'History preservation', 'order' => 11],
                ['key' => 'celebrate_team', 'label' => 'Celebrate and formally recognise team contributions', 'desc' => 'Culture building', 'order' => 12],
                ['key' => 'release_res', 'label' => 'Release team members and update availability', 'desc' => 'Resource offboarding', 'order' => 13],
                ['key' => 'closure_notice', 'label' => 'Send formal project closure notice', 'desc' => 'Wide stakeholder notification', 'order' => 14],
            ]
        ];

        foreach ($blueprint as $phase => $steps) {
            foreach ($steps as $step) {
                $existing = $this->timelineSteps()
                    ->where('key', $step['key'])
                    ->first();

                if ($existing) {
                    // Update descriptive metadata but preserve status
                    $existing->update([
                        'label' => $step['label'],
                        'description' => $step['desc'],
                        'order_position' => $step['order'],
                        'phase' => $phase // sync phase if changed
                    ]);
                } else {
                    // Create new step with default status
                    $this->timelineSteps()->create([
                        'phase' => $phase,
                        'key' => $step['key'],
                        'tenant_id' => $this->tenant_id,
                        'label' => $step['label'],
                        'description' => $step['desc'],
                        'order_position' => $step['order'],
                        'status' => $this->determineDefaultStepStatus($phase, $step['key'])
                    ]);
                }
            }
        }
    }

    protected function determineDefaultStepStatus($phase, $key)
    {
        // Only auto-complete if clearly already done via system markers
        if ($this->charter?->is_signed && ($key === 'signed_charter' || $phase === 'INITIATION')) {
            return 'complete';
        }
        
        return 'pending';
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeWon($query)
    {
        return $query->where('status', 'won');
    }

    public function scopeLost($query)
    {
        return $query->where('status', 'lost');
    }

    public function scopeForOwner($query, $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopeForPipeline($query, $pipelineId)
    {
        return $query->where('pipeline_id', $pipelineId);
    }

    public function scopeForStage($query, $stageId)
    {
        return $query->where('stage_id', $stageId);
    }

    /**
     * Use Hashid in URLs instead of raw ID.
     */
    public function getRouteKey()
    {
        return $this->public_id;
    }

    /**
     * Resolve the Hashid back to an ID for route binding.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->findByPublicId($value);
    }

    public function artifacts(): HasMany
    {
        return $this->hasMany(ProjectArtifact::class, 'project_id');
    }

    public function stakeholders(): HasMany
    {
        return $this->hasMany(ProjectStakeholder::class, 'opportunity_id');
    }
}
