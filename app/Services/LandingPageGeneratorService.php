<?php

namespace App\Services;

use App\Models\LandingPage;
use App\Models\BrandVoice;
use App\Models\DesignKit;
use App\Models\BusinessProfile;
use App\Prompts\LandingPagePrompts;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LandingPageGeneratorService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.key');
        $this->model = 'gpt-4-turbo';
    }

    /**
     * Generate a landing page from a user prompt
     */
    public function generateFromPrompt(
        string $prompt,
        int $tenantId,
        ?int $brandVoiceId = null,
        ?int $designKitId = null
    ): array {
        // Fetch brand context
        $brandVoice = $brandVoiceId ? BrandVoice::find($brandVoiceId) : BrandVoice::where('tenant_id', $tenantId)->active()->first();
        $designKit = $designKitId ? DesignKit::find($designKitId) : DesignKit::where('tenant_id', $tenantId)->active()->first();
        $businessProfile = BusinessProfile::where('tenant_id', $tenantId)->first();

        // Build prompts
        $systemPrompt = LandingPagePrompts::systemPrompt();
        $userPrompt = LandingPagePrompts::generatePagePrompt($prompt, $brandVoice, $designKit, $businessProfile);

        // Call OpenAI
        $response = $this->callOpenAI($systemPrompt, $userPrompt);

        // Parse and validate response
        $generatedContent = $this->parseAndValidate($response);

        return [
            'content' => $generatedContent['sections'] ?? [],
            'theme' => $generatedContent['theme'] ?? $this->getDefaultTheme($designKit),
            'brand_context' => [
                'brand_voice_id' => $brandVoice?->id,
                'design_kit_id' => $designKit?->id,
                'business_profile_id' => $businessProfile?->id,
            ],
            'generation_prompt' => $prompt,
        ];
    }

    /**
     * Regenerate a specific section with AI
     */
    public function regenerateSection(
        array $currentSection,
        string $instruction,
        ?int $brandVoiceId = null
    ): array {
        $brandVoice = $brandVoiceId ? BrandVoice::find($brandVoiceId) : null;

        $systemPrompt = LandingPagePrompts::systemPrompt();
        $userPrompt = LandingPagePrompts::regenerateSectionPrompt($currentSection, $instruction, $brandVoice);

        $response = $this->callOpenAI($systemPrompt, $userPrompt);
        $regenerated = $this->parseAndValidate($response);

        // Ensure it's a valid section
        if (!isset($regenerated['type']) || !isset($regenerated['data'])) {
            throw new \Exception('Invalid section structure returned from AI');
        }

        return $regenerated;
    }

    /**
     * Optimize content for SEO
     */
    public function optimizeForSEO(array $content, string $keywords): array
    {
        $systemPrompt = LandingPagePrompts::systemPrompt();
        $userPrompt = LandingPagePrompts::optimizeSEOPrompt($content, $keywords);

        $response = $this->callOpenAI($systemPrompt, $userPrompt);
        $optimized = $this->parseAndValidate($response);

        return $optimized['sections'] ?? $content;
    }

    /**
     * Generate FAQs based on page content
     */
    public function generateFAQs(array $pageContent, ?int $brandVoiceId = null): array
    {
        $brandVoice = $brandVoiceId ? BrandVoice::find($brandVoiceId) : null;

        $systemPrompt = LandingPagePrompts::systemPrompt();
        $userPrompt = LandingPagePrompts::generateFAQsPrompt($pageContent, $brandVoice);

        $response = $this->callOpenAI($systemPrompt, $userPrompt);
        $faqSection = $this->parseAndValidate($response);

        return $faqSection;
    }

    /**
     * Translate page content to another language
     */
    public function translatePage(array $content, string $targetLanguage): array
    {
        $systemPrompt = LandingPagePrompts::systemPrompt();
        $userPrompt = LandingPagePrompts::translatePrompt($content, $targetLanguage);

        $response = $this->callOpenAI($systemPrompt, $userPrompt);
        $translated = $this->parseAndValidate($response);

        return $translated['sections'] ?? $content;
    }

    /**
     * Call OpenAI API
     */
    private function callOpenAI(string $systemPrompt, string $userPrompt): string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 2000,
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to generate content from OpenAI');
            }

            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? '';

        } catch (\Exception $e) {
            Log::error('Landing Page Generation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception('AI generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Parse and validate AI response
     */
    private function parseAndValidate(string $response): array
    {
        // Clean up response (remove markdown code blocks if present)
        $cleaned = trim($response);
        $cleaned = preg_replace('/^```json\s*/m', '', $cleaned);
        $cleaned = preg_replace('/\s*```$/m', '', $cleaned);
        $cleaned = trim($cleaned);

        // Parse JSON
        $decoded = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON Parse Error', [
                'error' => json_last_error_msg(),
                'response' => $response,
            ]);
            throw new \Exception('Invalid JSON response from AI: ' . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Get default theme from design kit or fallback
     */
    private function getDefaultTheme(?DesignKit $designKit): array
    {
        $primaryColor = '#1FBF8F';
        
        if ($designKit && isset($designKit->colors['primary'])) {
            $primaryColor = $designKit->colors['primary'];
        }

        return [
            'primaryColor' => $primaryColor,
            'tone' => 'modern',
        ];
    }

    /**
     * Validate section structure
     */
    private function validateSection(array $section): bool
    {
        $validTypes = ['hero', 'features', 'testimonials', 'pricing', 'form'];
        
        if (!isset($section['type']) || !in_array($section['type'], $validTypes)) {
            return false;
        }

        if (!isset($section['data']) || !is_array($section['data'])) {
            return false;
        }

        return true;
    }
}
