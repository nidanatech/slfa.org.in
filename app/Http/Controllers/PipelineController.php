<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class PipelineController extends Controller
{
    public function index()
    {
        $tenantId = \Illuminate\Support\Facades\Auth::user()->tenant_id;
        $pipelines = \App\Models\Pipeline::where('tenant_id', $tenantId)
            ->with(['stages' => function($query) {
                $query->orderBy('order_index');
            }, 'channelDefaults', 'stageRules'])
            ->withCount('opportunities')
            ->withSum('opportunities', 'value_amount')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'description' => $p->description,
                    'is_active' => $p->is_active,
                    'ai_sorting_enabled' => $p->ai_sorting_enabled,
                    'ai_confidence_threshold' => $p->ai_confidence_threshold,
                    'allow_stage_downgrade' => $p->allow_stage_downgrade,
                    'ai_settings' => $p->ai_settings,
                    'stages' => $p->stages,
                    'channel_defaults' => $p->channelDefaults,
                    'stage_rules' => $p->stageRules,
                    'deals' => $p->opportunities_count,
                    'value' => '$' . number_format($p->opportunities_sum_value_amount ?? 0, 0),
                ];
            });

        $activeChannels = \App\Models\Channel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return Inertia::render('Pipelines/Index', [
            'pipelines' => $pipelines,
            'channelTypes' => ['whatsapp', 'instagram', 'facebook', 'email', 'sms', 'webchat', 'api'],
            'activeChannels' => $activeChannels,
        ]);
    }

    public function create()
    {
        // For modal or page
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'ai_sorting_enabled' => 'boolean',
            'ai_confidence_threshold' => 'numeric|min:0|max:1',
            'allow_stage_downgrade' => 'boolean',
            'ai_settings' => 'nullable|array',
            'stages' => 'required|array|min:1',
            'stages.*.name' => 'required|string|max:255',
            'stages.*.description' => 'nullable|string',
            'stages.*.color' => 'nullable|string',
            'stages.*.stage_type' => 'nullable|string|in:open,won,lost,spam',
            'stages.*.is_final' => 'boolean',
            'channel_defaults' => 'nullable|array',
            'channel_defaults.*.channel' => 'required|string',
            'channel_defaults.*.priority' => 'required|integer',
        ]);

        $tenantId = \Illuminate\Support\Facades\Auth::user()->tenant_id;

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request, $tenantId) {
                $pipeline = \App\Models\Pipeline::create([
                    'tenant_id' => $tenantId,
                    'name' => $request->name,
                    'description' => $request->description,
                    'is_active' => $request->is_active ?? true,
                    'ai_sorting_enabled' => $request->ai_sorting_enabled ?? false,
                    'ai_confidence_threshold' => $request->ai_confidence_threshold ?? 0.7,
                    'allow_stage_downgrade' => $request->allow_stage_downgrade ?? false,
                    'ai_settings' => $request->ai_settings,
                    'created_by' => \Illuminate\Support\Facades\Auth::id(),
                    'is_default' => \App\Models\Pipeline::where('tenant_id', $tenantId)->count() === 0,
                ]);

                $stageMapping = [];
                foreach ($request->stages as $index => $stageData) {
                    $newStage = $pipeline->stages()->create([
                        'name' => $stageData['name'],
                        'order_index' => $index,
                        'probability' => isset($stageData['probability']) ? $stageData['probability'] : null,
                        'is_final' => $stageData['is_final'] ?? false,
                        'color' => $stageData['color'] ?? '#64748b',
                        'description' => $stageData['description'] ?? null,
                        'stage_type' => $stageData['stage_type'] ?? 'open',
                    ]);

                    if (isset($stageData['id'])) {
                        $stageMapping[$stageData['id']] = $newStage->id;
                    }
                }

                if ($request->has('channel_defaults')) {
                    foreach ($request->channel_defaults as $default) {
                        $pipeline->channelDefaults()->create([
                            'channel' => $default['channel'],
                            'priority' => $default['priority'],
                        ]);
                    }
                }

                if ($request->has('stage_rules')) {
                    foreach ($request->stage_rules as $rule) {
                        $targetStageId = $rule['target_stage_id'];
                        // Use mapped ID if it exists (for new stages with temp IDs)
                        if (isset($stageMapping[$targetStageId])) {
                            $targetStageId = $stageMapping[$targetStageId];
                        }

                        $pipeline->stageRules()->create([
                            'target_stage_id' => $targetStageId,
                            'conditions_json' => $rule['conditions_json'],
                            'priority' => $rule['priority'] ?? 0,
                        ]);
                    }
                }
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == '23000') {
                return back()->withErrors(['stages' => 'An integrity error occurred while saving the pipeline. Please check your stages and rules.']);
            }
            throw $e;
        }

    }

    public function update(Request $request, \App\Models\Pipeline $pipeline)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'ai_sorting_enabled' => 'boolean',
            'ai_confidence_threshold' => 'numeric|min:0|max:1',
            'allow_stage_downgrade' => 'boolean',
            'ai_settings' => 'nullable|array',
            'stages' => 'required|array|min:1',
            'stages.*.name' => 'required|string|max:255',
            'stages.*.id' => 'nullable|integer', // Relaxed validation to allow temporary timestamps
            'stages.*.is_final' => 'boolean',
            'stages.*.color' => 'nullable|string',
            'stages.*.stage_type' => 'nullable|string|in:open,won,lost,spam',
            'channel_defaults' => 'nullable|array',
            'channel_defaults.*.channel' => 'required|string',
            'channel_defaults.*.priority' => 'required|integer',
            'stage_rules' => 'nullable|array',
            'stage_rules.*.target_stage_id' => 'required',
            'stage_rules.*.conditions_json' => 'required|array',
            'stage_rules.*.priority' => 'nullable|integer',
        ]);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request, $pipeline) {
                // Update Pipeline
                $pipeline->update([
                    'name' => $request->name,
                    'description' => $request->description,
                    'is_active' => $request->is_active ?? true,
                    'ai_sorting_enabled' => $request->ai_sorting_enabled ?? false,
                    'ai_confidence_threshold' => $request->ai_confidence_threshold ?? 0.7,
                    'allow_stage_downgrade' => $request->allow_stage_downgrade ?? false,
                    'ai_settings' => $request->ai_settings,
                ]);

                // Current Stage IDs in DB
                $existingStageIds = $pipeline->stages()->pluck('id')->toArray();
                
                // Process Input Stages
                $processedIds = [];
                $stageMapping = [];
                
                foreach ($request->stages as $index => $stageData) {
                    if (isset($stageData['id']) && in_array($stageData['id'], $existingStageIds)) {
                        // Update existing
                        $pipeline->stages()->where('id', $stageData['id'])->update([
                            'name' => $stageData['name'],
                            'order_index' => $index,
                            'probability' => $stageData['probability'] ?? null,
                            'is_closed' => $stageData['is_closed'] ?? false,
                            'is_won' => $stageData['is_won'] ?? false,
                            'is_final' => $stageData['is_final'] ?? false,
                            'color' => $stageData['color'] ?? '#64748b',
                            'description' => $stageData['description'] ?? null,
                            'stage_type' => $stageData['stage_type'] ?? 'open',
                        ]);
                        $processedIds[] = $stageData['id'];
                        $stageMapping[$stageData['id']] = $stageData['id'];
                    } else {
                        // Create new
                        $newStage = $pipeline->stages()->create([
                            'name' => $stageData['name'],
                            'order_index' => $index,
                            'probability' => $stageData['probability'] ?? null,
                            'is_closed' => $stageData['is_closed'] ?? false,
                            'is_won' => $stageData['is_won'] ?? false,
                            'is_final' => $stageData['is_final'] ?? false,
                            'color' => $stageData['color'] ?? '#64748b',
                            'description' => $stageData['description'] ?? null,
                            'stage_type' => $stageData['stage_type'] ?? 'open',
                        ]);
                        if (isset($stageData['id'])) {
                            $stageMapping[$stageData['id']] = $newStage->id;
                        }
                    }
                }

                // Delete removed stages
                $stagesToDelete = array_diff($existingStageIds, $processedIds);
                if (!empty($stagesToDelete)) {
                    // This will fail if stages have opportunities due to FK constraints (restrict)
                    // We let it fail and catch the exception to notify user.
                    \App\Models\PipelineStage::whereIn('id', $stagesToDelete)->delete();
                }

                // Sync Channel Defaults
                $pipeline->channelDefaults()->delete(); // Simple replace
                if ($request->has('channel_defaults')) {
                    foreach ($request->channel_defaults as $default) {
                        $pipeline->channelDefaults()->create([
                            'channel' => $default['channel'],
                            'priority' => $default['priority'],
                        ]);
                    }
                }

                // Sync Stage Rules
                $pipeline->stageRules()->delete(); // Simple replace
                if ($request->has('stage_rules')) {
                    foreach ($request->stage_rules as $rule) {
                        $targetStageId = $rule['target_stage_id'];
                        // Use mapped ID if it exists (for new stages with temp IDs)
                        if (isset($stageMapping[$targetStageId])) {
                            $targetStageId = $stageMapping[$targetStageId];
                        }

                        $pipeline->stageRules()->create([
                            'target_stage_id' => $targetStageId,
                            'conditions_json' => $rule['conditions_json'],
                            'priority' => $rule['priority'] ?? 0,
                        ]);
                    }
                }
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == '23000') { // Integrity constraint violation
                return back()->withErrors(['stages' => 'Cannot delete stages that have active opportunities. Please move opportunities to another stage first.']);
            }
            throw $e;
        }

        return redirect()->back();
    }

    public function destroy(\Illuminate\Http\Request $request, \App\Models\Pipeline $pipeline)
    {
        $action = $request->input('action', 'none'); // none, delete_all, move

        // Check if has opportunities and no action was specified (prevent accident)
        if ($pipeline->opportunities()->exists() && $action === 'none') {
            return redirect()->back()->with('error', 'Cannot delete pipeline because it still has active opportunities.');
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            if ($action === 'delete_all') {
                $pipeline->opportunities()->delete();
            } elseif ($action === 'move') {
                $targetPipelineId = $request->input('target_pipeline_id');
                $stageMapping = $request->input('stage_mapping', []); // [old_stage_id => new_stage_id]

                foreach ($pipeline->opportunities as $opportunity) {
                    $newStageId = $stageMapping[$opportunity->stage_id] ?? null;
                    if ($newStageId) {
                        $opportunity->update([
                            'pipeline_id' => $targetPipelineId,
                            'stage_id' => $newStageId
                        ]);
                    }
                }
            }

            $pipeline->delete();
            \Illuminate\Support\Facades\DB::commit();
            
            return redirect()->route('pipelines.index')->with('success', 'Pipeline deleted successfully.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete pipeline: ' . $e->getMessage());
        }
    }

    public function duplicate(\App\Models\Pipeline $pipeline)
    {
        $newPipeline = $pipeline->replicate();
        $newPipeline->name = $pipeline->name . ' (Copy)';
        $newPipeline->is_default = false;
        $newPipeline->created_by = \Illuminate\Support\Facades\Auth::id();
        $newPipeline->save();

        foreach ($pipeline->stages as $stage) {
            $newStage = $stage->replicate();
            $newStage->pipeline_id = $newPipeline->id;
            $newStage->save();
        }

        return redirect()->back();
    }

    public function generate(Request $request, \App\Services\Pipelines\AiPipelineGeneratorService $generator)
    {
        $request->validate(['prompt' => 'required|string|min:5|max:1000']);
        $tenant = \Illuminate\Support\Facades\Auth::user()->tenant;
        
        $result = $generator->generateFromPrompt($request->prompt, $tenant->openai_key);
        
        if (!$result) {
            return response()->json(['message' => 'Failed to generate pipeline'], 500);
        }

        return response()->json($result);
    }
}
