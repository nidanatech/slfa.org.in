<?php

namespace App\Jobs;

use App\Models\CompetitorChange;
use App\Models\CompetitorInsight;
use App\Services\AiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeCompetitorChangeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $change;

    public function __construct(CompetitorChange $change)
    {
        $this->change = $change;
    }

    public function handle(AiService $aiService): void
    {
        Log::info("Analyzing change {$this->change->id} for competitor {$this->change->competitor->name}");

        try {
            // Build context for AI analysis
            $context = $this->buildAnalysisContext();

            // Generate AI analysis
            $prompt = $this->buildAnalysisPrompt($context);
            $analysis = $aiService->generateText($prompt, [
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ]);

            // Parse AI response (expecting JSON)
            $parsedAnalysis = $this->parseAiResponse($analysis);

            // Create insight
            $insight = CompetitorInsight::create([
                'tenant_id' => $this->change->competitor->tenant_id,
                'competitor_id' => $this->change->competitor_id,
                'change_id' => $this->change->id,
                'insight_type' => $parsedAnalysis['type'] ?? 'tactical',
                'priority' => $this->determinePriority($parsedAnalysis),
                'title' => $parsedAnalysis['title'] ?? "Analysis: {$this->change->title}",
                'summary' => $parsedAnalysis['summary'] ?? 'AI analysis completed.',
                'analysis' => $parsedAnalysis['analysis'] ?? $analysis,
                'recommendations' => $parsedAnalysis['recommendations'] ?? [],
                'affected_areas' => $parsedAnalysis['affected_areas'] ?? [],
                'confidence_score' => $parsedAnalysis['confidence'] ?? 0.75,
                'generated_by' => 'ai',
                'generated_at' => now(),
            ]);

            Log::info("Created insight {$insight->id} for change {$this->change->id}");

            // Create alert if high priority
            if (in_array($insight->priority, ['high', 'critical'])) {
                $insight->createAlert('in_app');
            }

        } catch (\Exception $e) {
            Log::error("Failed to analyze change {$this->change->id}: " . $e->getMessage());
        }
    }

    private function buildAnalysisContext(): array
    {
        return [
            'competitor_name' => $this->change->competitor->name,
            'competitor_industry' => $this->change->competitor->industry,
            'competitor_website' => $this->change->competitor->website_url,
            'change_type' => $this->change->change_type,
            'change_category' => $this->change->category,
            'change_title' => $this->change->title,
            'change_description' => $this->change->description,
            'severity' => $this->change->severity,
            'diff_data' => $this->change->diff_data,
        ];
    }

    private function buildAnalysisPrompt(array $context): string
    {
        return <<<PROMPT
You are a competitive intelligence analyst. Analyze the following competitor change and provide strategic insights.

Competitor: {$context['competitor_name']}
Industry: {$context['competitor_industry']}
Website: {$context['competitor_website']}

Change Detected:
Type: {$context['change_type']}
Category: {$context['change_category']}
Title: {$context['change_title']}
Description: {$context['change_description']}
Severity: {$context['severity']}

Please analyze this change and provide:
1. Type of insight (strategic, tactical, threat, or opportunity)
2. A concise title (max 100 characters)
3. A brief summary (2-3 sentences)
4. Detailed analysis (what this means for our business)
5. 3-5 actionable recommendations
6. Affected business areas (e.g., pricing, features, marketing, product)
7. Confidence score (0.0 to 1.0)

Respond ONLY with valid JSON in this exact format:
{
  "type": "strategic|tactical|threat|opportunity",
  "title": "Brief title",
  "summary": "2-3 sentence summary",
  "analysis": "Detailed analysis paragraph",
  "recommendations": ["Recommendation 1", "Recommendation 2", "Recommendation 3"],
  "affected_areas": ["pricing", "features", "marketing"],
  "confidence": 0.85
}
PROMPT;
    }

    private function parseAiResponse(string $response): array
    {
        // Try to extract JSON from response
        if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json) {
                return $json;
            }
        }

        // Fallback if JSON parsing fails
        return [
            'type' => 'tactical',
            'title' => 'Competitor Change Detected',
            'summary' => 'A change was detected in competitor activity.',
            'analysis' => $response,
            'recommendations' => ['Monitor for further changes', 'Review our positioning'],
            'affected_areas' => ['general'],
            'confidence' => 0.5,
        ];
    }

    private function determinePriority(array $analysis): string
    {
        // Determine priority based on type and confidence
        $type = $analysis['type'] ?? 'tactical';
        $confidence = $analysis['confidence'] ?? 0.5;

        if ($type === 'threat' && $confidence > 0.7) {
            return 'critical';
        }

        if (in_array($type, ['strategic', 'opportunity']) && $confidence > 0.7) {
            return 'high';
        }

        if ($confidence > 0.6) {
            return 'medium';
        }

        return 'low';
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("AnalyzeCompetitorChangeJob failed for change {$this->change->id}: " . $exception->getMessage());
    }
}
