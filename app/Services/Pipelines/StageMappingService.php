<?php

namespace App\Services\Pipelines;

use App\Models\Pipeline;
use App\Models\PipelineStage;

class StageMappingService
{
    public function determineTargetStage(Pipeline $pipeline, array $aiOutput): ?PipelineStage
    {
        $rules = $pipeline->stageRules()->orderBy('priority', 'desc')->get();

        foreach ($rules as $rule) {
            if ($this->matchesCondition($rule->conditions_json, $aiOutput)) {
                return $rule->targetStage;
            }
        }

        return null;
    }

    protected function matchesCondition(array $conditions, array $aiOutput): bool
    {
        foreach ($conditions as $field => $requiredValue) {
            // If condition is 'any', it always matches as long as it's not strictly 'any' in AI output
            if ($requiredValue === 'any') {
                continue;
            }

            if (!isset($aiOutput[$field])) {
                return false;
            }

            $actualValue = $aiOutput[$field];

            if (is_array($requiredValue)) {
                if (!in_array($actualValue, $requiredValue)) {
                    return false;
                }
            } else {
                if ($actualValue != $requiredValue) {
                    return false;
                }
            }
        }

        return true;
    }
}
