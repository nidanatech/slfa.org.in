<?php

namespace App\Prompts;

class LandingPagePrompts
{
    /**
     * System prompt for landing page generation
     */
    public static function systemPrompt(): string
    {
        return <<<PROMPT
You are a professional landing page designer and conversion optimization expert. Your task is to generate landing pages as structured JSON only.

CRITICAL RULES:
1. Output ONLY valid JSON - no markdown, no explanations, no code blocks
2. Use only these section types: hero, features, testimonials, pricing, form
3. Follow the exact schema provided
4. Incorporate brand voice and design kit data naturally
5. Keep content concise, persuasive, and conversion-focused
6. Use action-oriented language
7. Include social proof when appropriate
8. Optimize for mobile-first design

SECTION SCHEMAS:

Hero Section:
{
  "type": "hero",
  "data": {
    "title": "Compelling headline (max 60 chars)",
    "subtitle": "Supporting text (max 150 chars)",
    "cta": "Action button text (max 25 chars)",
    "bgImage": null
  }
}

Features Section:
{
  "type": "features",
  "data": {
    "title": "Section heading",
    "items": [
      {
        "icon": "icon-name",
        "title": "Feature title",
        "description": "Brief description"
      }
    ]
  }
}

Testimonials Section:
{
  "type": "testimonials",
  "data": {
    "title": "Social Proof",
    "items": [
      {
        "name": "Customer name",
        "role": "Job title",
        "content": "Testimonial text",
        "avatar": null
      }
    ]
  }
}

Pricing Section:
{
  "type": "pricing",
  "data": {
    "title": "Pricing Plans",
    "items": [
      {
        "name": "Plan name",
        "price": "99",
        "currency": "USD",
        "period": "month",
        "features": ["Feature 1", "Feature 2"],
        "cta": "Get Started",
        "highlighted": false
      }
    ]
  }
}

Form Section:
{
  "type": "form",
  "data": {
    "title": "Form heading",
    "subtitle": "Form description",
    "fields": [
      {
        "label": "Field label",
        "type": "text|email|tel|textarea",
        "required": true
      }
    ],
    "submitText": "Submit button text"
  }
}

OUTPUT FORMAT:
{
  "sections": [/* array of sections */],
  "theme": {
    "primaryColor": "#hex",
    "tone": "modern|luxury|corporate|friendly"
  }
}
PROMPT;
    }

    /**
     * User prompt template for generating a landing page
     */
    public static function generatePagePrompt(
        string $userPrompt,
        ?object $brandVoice = null,
        ?object $designKit = null,
        ?object $businessProfile = null
    ): string {
        $brandContext = self::buildBrandContext($brandVoice, $businessProfile);
        $designContext = self::buildDesignContext($designKit);

        return <<<PROMPT
Create a high-converting landing page for: {$userPrompt}

{$brandContext}

{$designContext}

Generate 3-5 sections that:
- Start with a compelling hero section
- Include key features or benefits
- Add social proof if relevant
- End with a clear call-to-action or form
- Use the brand voice and design style consistently

Return ONLY the JSON structure, no additional text.
PROMPT;
    }

    /**
     * Prompt for regenerating a specific section
     */
    public static function regenerateSectionPrompt(
        array $currentSection,
        string $instruction,
        ?object $brandVoice = null
    ): string {
        $sectionJson = json_encode($currentSection, JSON_PRETTY_PRINT);
        $brandTone = $brandVoice ? "Brand tone: {$brandVoice->brand_description}" : "";

        return <<<PROMPT
Current section:
{$sectionJson}

Instruction: {$instruction}

{$brandTone}

Regenerate this section following the instruction while maintaining the same section type and schema.
Return ONLY the updated section JSON, no additional text.
PROMPT;
    }

    /**
     * Prompt for SEO optimization
     */
    public static function optimizeSEOPrompt(array $content, string $keywords): string
    {
        $contentJson = json_encode($content, JSON_PRETTY_PRINT);

        return <<<PROMPT
Optimize this landing page content for SEO targeting these keywords: {$keywords}

Current content:
{$contentJson}

Tasks:
1. Naturally incorporate keywords into titles and descriptions
2. Improve heading hierarchy
3. Add compelling meta descriptions
4. Ensure content is scannable and engaging

Return the optimized content in the same JSON structure.
PROMPT;
    }

    /**
     * Prompt for FAQ generation
     */
    public static function generateFAQsPrompt(array $pageContent, ?object $brandVoice = null): string
    {
        $contentSummary = self::summarizeContent($pageContent);
        $industry = $brandVoice?->business_type ?? 'general';

        return <<<PROMPT
Based on this landing page content, generate 5-8 frequently asked questions and answers:

Page summary: {$contentSummary}
Industry: {$industry}

Generate questions that:
- Address common customer concerns
- Overcome objections
- Provide value and build trust
- Are specific to the product/service

Return as a form section with this structure:
{
  "type": "form",
  "data": {
    "title": "Frequently Asked Questions",
    "items": [
      {
        "question": "Question text?",
        "answer": "Detailed answer"
      }
    ]
  }
}
PROMPT;
    }

    /**
     * Prompt for translation
     */
    public static function translatePrompt(array $content, string $targetLanguage): string
    {
        $contentJson = json_encode($content, JSON_PRETTY_PRINT);

        return <<<PROMPT
Translate this landing page content to {$targetLanguage}.

Current content:
{$contentJson}

Rules:
- Maintain the exact JSON structure
- Translate all text content naturally
- Preserve tone and persuasiveness
- Keep technical terms appropriate for the market
- Maintain CTA effectiveness

Return the translated content in the same JSON structure.
PROMPT;
    }

    /**
     * Build brand context string
     */
    private static function buildBrandContext(?object $brandVoice, ?object $businessProfile): string
    {
        if (!$brandVoice && !$businessProfile) {
            return "Brand Context: Not specified";
        }

        $context = "Brand Context:\n";

        if ($businessProfile) {
            $context .= "- Business: {$businessProfile->friendly_name}\n";
        }

        if ($brandVoice) {
            $context .= "- Industry: {$brandVoice->business_type}\n";
            $context .= "- Target Audience: {$brandVoice->target_audience}\n";
            $context .= "- Brand Promise: {$brandVoice->brand_promise}\n";
            $context .= "- USP: {$brandVoice->unique_selling_proposition}\n";
            $context .= "- Voice: {$brandVoice->brand_description}\n";
            
            if ($brandVoice->customer_pain_points) {
                $context .= "- Pain Points: {$brandVoice->customer_pain_points}\n";
            }
        }

        return $context;
    }

    /**
     * Build design context string
     */
    private static function buildDesignContext(?object $designKit): string
    {
        if (!$designKit) {
            return "Design Style: Modern, clean, professional";
        }

        $colors = $designKit->colors ?? [];
        $primaryColor = $colors['primary'] ?? '#1FBF8F';
        
        $context = "Design Style:\n";
        $context .= "- Primary Color: {$primaryColor}\n";
        $context .= "- Template Type: {$designKit->template_type}\n";
        $context .= "- Style: Clean, modern, professional\n";

        return $context;
    }

    /**
     * Summarize page content for FAQ generation
     */
    private static function summarizeContent(array $pageContent): string
    {
        $summary = [];
        
        foreach ($pageContent as $section) {
            if ($section['type'] === 'hero' && isset($section['data']['title'])) {
                $summary[] = "Main offer: " . $section['data']['title'];
            }
            if ($section['type'] === 'features' && isset($section['data']['items'])) {
                $features = array_column($section['data']['items'], 'title');
                $summary[] = "Features: " . implode(', ', array_slice($features, 0, 3));
            }
        }

        return implode('. ', $summary);
    }
}
