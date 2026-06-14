<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use App\Services\ImageGenerationService;
use App\Models\BrandVoice;
use App\Models\DesignKit;
use App\Models\SocialPlannerSettings;
use Illuminate\Http\Request;

class AIContentGeneratorController extends Controller
{
    public function generateContent(Request $request)
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:1000',
            'brand_voice_id' => 'nullable|exists:brand_voices,id',
            'design_kit_id' => 'nullable|exists:design_kits,id',
            'context_images' => 'nullable|array',
        ]);

        $settings = SocialPlannerSettings::getOrCreateForTenant(auth()->user()->tenant_id);
        $brandVoice = $validated['brand_voice_id'] ? BrandVoice::find($validated['brand_voice_id']) : null;

        // Build system prompt with brand context
        $systemPrompt = "You are a professional social media content creator. Create engaging, platform-appropriate content.";
        
        if ($brandVoice) {
            $systemPrompt .= "\n\nBrand Context:";
            $systemPrompt .= "\n- Brand: {$brandVoice->brand_name}";
            $systemPrompt .= "\n- Business Type: {$brandVoice->business_type}";
            $systemPrompt .= "\n- Description: {$brandVoice->brand_description}";
            $systemPrompt .= "\n- Target Audience: {$brandVoice->target_audience}";
            $systemPrompt .= "\n- Tone: {$brandVoice->tone_of_voice}";
            
            if ($brandVoice->key_messages) {
                $systemPrompt .= "\n- Key Messages: " . implode(', ', $brandVoice->key_messages);
            }
        }

        if ($settings->auto_hashtag_suggestions) {
            $systemPrompt .= "\n\nInclude 3-5 relevant hashtags at the end.";
        }

        if ($settings->auto_emoji_suggestions) {
            $systemPrompt .= "\n\nUse appropriate emojis to enhance engagement.";
        }

        try {
            $aiService = new AiService();
            $content = $aiService->generateText(
                $validated['prompt'],
                $systemPrompt,
                $settings->ai_max_tokens,
                $settings->ai_temperature
            );

            // Extract hashtags from content
            preg_match_all('/#(\w+)/', $content, $matches);
            $hashtags = $matches[1] ?? [];

            return response()->json([
                'success' => true,
                'content' => $content,
                'hashtags' => $hashtags,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate content: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generateImage(Request $request)
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:1000',
            'design_kit_id' => 'nullable|exists:design_kits,id',
            'selected_channels' => 'nullable|array',
            'selected_channels.*' => 'exists:channels,id',
        ]);

        $settings = SocialPlannerSettings::getOrCreateForTenant(auth()->user()->tenant_id);
        $designKit = $validated['design_kit_id'] ? DesignKit::find($validated['design_kit_id']) : null;

        // Determine optimal image size based on selected channels
        $imageSize = $this->determineOptimalImageSize($validated['selected_channels'] ?? []);

        // Enhance prompt with design kit colors
        $enhancedPrompt = $validated['prompt'];
        if ($designKit && $designKit->colors) {
            $colorList = implode(', ', array_slice($designKit->colors, 0, 3));
            $enhancedPrompt .= ". Use a color palette featuring: {$colorList}";
        }

        try {
            $imageService = new ImageGenerationService($settings->image_model_api_key);
            $imageUrl = $imageService->generate(
                $enhancedPrompt,
                $settings->image_model,
                $settings->image_quality,
                $imageSize
            );

            return response()->json([
                'success' => true,
                'image_url' => $imageUrl,
                'prompt' => $enhancedPrompt,
                'size' => $imageSize,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate image: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Determine optimal image size based on selected channels
     */
    private function determineOptimalImageSize(array $channelIds): string
    {
        if (empty($channelIds)) {
            return '1024x1024'; // Default square
        }

        $channels = \App\Models\Channel::whereIn('id', $channelIds)->get();
        $platformTypes = $channels->pluck('type')->unique();

        // Platform-specific recommended sizes
        $platformSizes = [
            'instagram' => '1080x1080',  // Square for feed
            'facebook' => '1200x630',    // Landscape for link posts
            'twitter' => '1200x675',     // 16:9 for tweets
            'linkedin' => '1200x627',    // Landscape for posts
            'tiktok' => '1080x1920',     // Vertical 9:16
            'pinterest' => '1000x1500',  // Vertical 2:3
        ];

        // Priority order: Instagram > Facebook > Twitter > LinkedIn > TikTok
        $priorityOrder = ['instagram', 'facebook', 'twitter', 'linkedin', 'tiktok', 'pinterest'];

        foreach ($priorityOrder as $platform) {
            if ($platformTypes->contains($platform)) {
                return $platformSizes[$platform];
            }
        }

        // If multiple platforms with different aspect ratios, use square (most versatile)
        if ($platformTypes->count() > 2) {
            return '1080x1080';
        }

        return '1024x1024'; // Default
    }

    public function enhanceContent(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'enhancement_type' => 'required|in:shorten,lengthen,professional,casual,add_emojis,add_hashtags',
        ]);

        $settings = SocialPlannerSettings::getOrCreateForTenant(auth()->user()->tenant_id);

        $prompts = [
            'shorten' => "Make this social media post more concise while keeping the key message:",
            'lengthen' => "Expand this social media post with more details and context:",
            'professional' => "Rewrite this in a more professional tone:",
            'casual' => "Rewrite this in a more casual, friendly tone:",
            'add_emojis' => "Add appropriate emojis to this post to increase engagement:",
            'add_hashtags' => "Add 3-5 relevant hashtags to this post:",
        ];

        $systemPrompt = $prompts[$validated['enhancement_type']];

        try {
            $aiService = new AiService();
            $enhancedContent = $aiService->generateText(
                $validated['content'],
                $systemPrompt,
                $settings->ai_max_tokens,
                $settings->ai_temperature
            );

            return response()->json([
                'success' => true,
                'content' => $enhancedContent,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to enhance content: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function generateMeetingInvite(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:opportunities,id',
            'channel' => 'required|in:email,whatsapp,facebook,sms',
            'subject' => 'nullable|string',
            'details' => 'nullable|string',
            'mode' => 'nullable|string', // 'invitation' or 'synthesis'
            'date' => 'nullable|string',
            'time' => 'nullable|string',
            'location' => 'nullable|string',
            'timezone' => 'nullable|string',
            'business' => 'nullable|array'
        ]);

        $project = \App\Models\Opportunity::with('charter', 'contact')->find($validated['project_id']);
        $channel = $validated['channel'];
        $objectives = $project->charter?->objectives ?? 'Project requirements overview.';
        $mode = $validated['mode'] ?? 'invitation';
        
        if ($mode === 'synthesis') {
            $systemPrompt = "You are a Senior Project Management Consultant. Your task is to synthesize raw project data into a high-fidelity Detailed Internal Project Understanding Document for internal use and Project Charter creation. Aling with PMBOK standards.\n";
            $systemPrompt .= "Tone: Strategic, technical, and objective.\n";
            $systemPrompt .= "Format: Use professional HTML. Use <h2> for sub-sections, <ul> for lists. No <html> or <body> tags. ONLY the content within a <div>.\n";
            $systemPrompt .= "IMPORTANT: Return a JSON object with 'subject' (Document Title) and 'content' (The Document Body) keys.";

            $userPrompt = $validated['details'] ?? "Synthesize the project baseline for '{$project->name}'.";
        } else {
            $systemPrompt = "You are a professional Project Management Assistant. Generate meeting invitations aligned with PMBOK standards.\n";
            $systemPrompt .= "Channel: {$channel}. Tone: Professional, clear, and action-oriented.\n";
            
            if ($channel === 'email') {
                $systemPrompt .= "Format: Use professional HTML. Use <h2> for sub-sections (like 'Meeting Details' or 'Agenda'), <ul> for lists. No <html> or <body> tags. ONLY the content within a <div>.\n";
                $systemPrompt .= "CRITICAL: Do NOT include a main title or repeat the subject line inside the 'content' field. Start directly with the greeting 'Dear [Name]'.\n";
                $systemPrompt .= "IMPORTANT: Do NOT wrap the response in markdown code blocks like ```html. Return ONLY the JSON code.";
            } else {
                $systemPrompt .= "Format: Plain text with professional spacing. Use bullet points (•).\n";
                $systemPrompt .= "CRITICAL: Do NOT repeat the subject line. Start directly with the greeting or the message.\n";
            }

            $userPrompt = "Generate a {$channel} invitation for a 'Requirement Overview' meeting for the project '{$project->name}'.\n";
            $userPrompt .= "Client Name: {$project->contact?->name}\n";
            $userPrompt .= "Project Context: " . ($project->description ?? $objectives) . "\n";
            $userPrompt .= "Date: " . ($validated['date'] ?? 'TBD') . " at " . ($validated['time'] ?? 'TBD') . " (" . ($validated['timezone'] ?? 'UTC') . ")\n";
            $userPrompt .= "Location/Link: " . ($validated['location'] ?? 'Online') . "\n";
            $userPrompt .= "Key Objectives:\n{$objectives}\n";
            
            if ($request->filled('business')) {
                $userPrompt .= "\nFrom: " . ($validated['business']['name'] ?? 'Project Team') . "\n";
                $userPrompt .= "Contact: " . ($validated['business']['email'] ?? '') . " " . ($validated['business']['phone'] ?? '') . "\n";
            }

            if ($request->filled('details')) {
                $userPrompt .= "\nAdditional Context: {$request->details}";
            }

            $userPrompt .= "\nIMPORTANT: Do NOT use placeholders like [Insert Date]. Use provided data. Return ONLY a valid JSON object with 'subject' and 'content' keys. Ensure 'content' starts with the greeting and contains NO headers that repeat the subject.";
        }

        try {
            $aiService = new AiService();
            $rawResponse = $aiService->generateText($userPrompt, $systemPrompt);
            
            // Clean markdown blocks if present
            $jsonStr = $rawResponse;
            if (str_contains($jsonStr, '```')) {
                $jsonStr = preg_replace('/```[a-z]*\n/i', '', $jsonStr);
                $jsonStr = str_replace('```', '', $jsonStr);
                $jsonStr = trim($jsonStr);
            }

            $data = json_decode($jsonStr, true);
            
            // Fallback if AI fails JSON or returns string
            if (!$data || !isset($data['content'])) {
                $data = [
                    'subject' => $validated['subject'] ?? "Meeting Invitation: {$project->name}",
                    'content' => $jsonStr
                ];
            }
            
            return response()->json([
                'success' => true,
                'subject' => $data['subject'] ?? '',
                'content' => $data['content']
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function transcribe(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|mimes:webm,mp3,wav,m4a,ogg,mp4', // Allow common audio formats from browsers
        ]);

        try {
            $aiService = new \App\Services\AiService();
            $text = $aiService->transcribe($request->file('audio'));

            return response()->json([
                'success' => true,
                'text' => $text,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Transcription failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generateScope(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:opportunities,id',
            'goals' => 'nullable|string',
            'deliverables' => 'nullable|string',
            'constraints' => 'nullable|string',
            'assumptions' => 'nullable|string',
        ]);

        $project = \App\Models\Opportunity::with('contact')->find($validated['project_id']);
        
        $systemPrompt = "You are a Senior Project Management Consultant (PMP certified). Your task is to synthesize raw project identification data into a professional 'Project Scope Baseline' document.\n";
        $systemPrompt .= "Format: Use professional HTML. Use <h2> for sub-sections, <ul> for lists. No <html> or <body> tags. ONLY the content within a <div>.\n";
        $systemPrompt .= "Tone: Objective, technical, and precise. Ensure the document follows PMBOK Initiation standards.";

        $userPrompt = "Generate a Unified Scope Baseline for project: '{$project->name}'.\n";
        $userPrompt .= "Primary Goals: " . ($validated['goals'] ?? 'Not specified') . "\n";
        $userPrompt .= "Critical Deliverables: " . ($validated['deliverables'] ?? 'Not specified') . "\n";
        $userPrompt .= "Project Constraints: " . ($validated['constraints'] ?? 'Not specified') . "\n";
        $userPrompt .= "Critical Assumptions: " . ($validated['assumptions'] ?? 'Not specified') . "\n";
        $userPrompt .= "Project Context: " . ($project->description ?? 'Initial initiation phase') . "\n";
        
        try {
            $aiService = new \App\Services\AiService();
            $content = $aiService->generateText($userPrompt, $systemPrompt);
            
            return response()->json([
                'success' => true,
                'content' => $content
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    public function generateProjectCharter(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:opportunities,id',
            'additional_context' => 'nullable|string',
        ]);

        $project = \App\Models\Opportunity::with(['artifacts', 'stakeholders.contact', 'product'])->find($validated['project_id']);
        
        // 1. Data Aggregation
        $meetingArtifact = $project->artifacts->where('step_key', 'req_overview')->first();
        $scopeArtifact = $project->artifacts->where('step_key', 'doc_scope')->first();
        $matrixArtifact = $project->artifacts->where('step_key', 'reg_stakeholders')->first();

        $aggregation = [
            'meeting_context' => $meetingArtifact?->generated_content,
            'scope_baseline' => $scopeArtifact?->form_data, // Contains goals, deliverables, etc.
            'stakeholders' => $project->stakeholders->map(fn($s) => [
                'name' => $s->contact?->name,
                'role' => $s->role_type . ($s->role ? " ({$s->role})" : ""),
                'classification' => $s->classification,
                'strategy' => $s->strategy
            ]),
        ];

        $businessProfile = \App\Models\BusinessProfile::where('tenant_id', auth()->user()->tenant_id)->first();
        $businessCurrency = $businessProfile?->currency ?? 'USD';

        // 2. Build High-Fidelity Prompt
        $systemPrompt = "You are an elite PMP-Certified Project Management Consultant.
Based on the aggregated initiation data provided, draft a high-fidelity, EXTENSIVE Project Charter.
Combine the technical scope, stakeholder dynamics, and meeting requirements into a unified strategic baseline.

The output MUST be a valid JSON object with exactly these fields:
{
    \"purpose_justification\": \"Detailed explanation of business need and strategic alignment.\",
    \"objectives\": \"Extensive measurable goals with Success Criteria (KPIs).\",
    \"high_level_requirements\": \"Comprehensive list of essential features and stakeholder expectations.\",
    \"description\": \"Professional multi-paragraph summary of the project vision.\",
    \"deliverables\": \"Categorized list of outputs.\",
    \"in_scope\": \"Detailed list of everything included.\",
    \"out_of_scope\": \"Explicit exclusions.\",
    \"overall_risk\": \"In-depth analysis of primary threats or technical hurdles.\",
    \"milestone_summary\": \"List of major phases with estimated timelines.\",
    \"budget_summary\": \"Financial breakdown summary.\",
    \"stakeholder_list\": \"Summary of key players and their alignment.\",
    \"approval_requirements\": \"Definition of 'Done' and authority levels.\",
    \"pm_authority\": \"Authority level of the Business Lead.\",
    \"sponsor_authority\": \"The client sponsor's role and ultimate authority.\",
    \"estimated_duration_weeks\": \"integer value\",
    \"budget_amount\": \"float value\",
    \"currency\": \"{$businessCurrency}\",
    \"estimated_start_date\": \"YYYY-MM-DD or null\",
    \"estimated_end_date\": \"YYYY-MM-DD or null\",
    \"assumptions\": \"List of critical assumptions.\",
    \"constraints\": \"Real-world constraints.\"
}";

        $userPrompt = "Aggregate and Generate Project Charter for: '{$project->name}' (Product: " . ($project->product?->name ?? 'N/A') . ")\n\n";
        $userPrompt .= "--- AGGREGATED INITIATION DATA ---\n";
        $userPrompt .= "1. REQUIREMENT CONTEXT (Meeting Notes):\n" . ($aggregation['meeting_context'] ?? 'N/A') . "\n\n";
        $userPrompt .= "2. SCOPE BASELINE:\n" . json_encode($aggregation['scope_baseline'], JSON_PRETTY_PRINT) . "\n\n";
        $userPrompt .= "3. STAKEHOLDER REGISTER:\n" . json_encode($aggregation['stakeholders'], JSON_PRETTY_PRINT) . "\n\n";
        
        if (!empty($validated['additional_context'])) {
            $userPrompt .= "4. ADDITIONAL STRATEGIC CONTEXT:\n{$validated['additional_context']}\n\n";
        }

        $userPrompt .= "Ensure the tone is executive and authoritative.";

        try {
            $aiService = new \App\Services\AiService();
            $response = $aiService->generateText($userPrompt, $systemPrompt, 2000);
            
            $cleanJson = str_replace(['```json', '```'], '', $response);
            $data = json_decode(trim($cleanJson), true);

            if (!$data) {
                return response()->json(['success' => false, 'error' => 'AI returned invalid JSON structure.'], 500);
            }

            return response()->json([
                'success' => true,
                'charter' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function generateRiskProfile(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:opportunities,id',
            'assumptions' => 'nullable|string',
            'constraints' => 'nullable|string',
        ]);

        $project = \App\Models\Opportunity::with('charter')->find($validated['project_id']);
        
        $systemPrompt = "You are a Senior Project Risk Manager (PMI-RMP certified).
Analyze the project context and synthesize a high-level Risk and Assumption Profile.
Follow PMBOK Initiation standards.
IMPORTANT: Generate AT LEAST 10 diverse high-fidelity risks (Strategic, Technical, Financial, Operational, etc.).

The output MUST be a valid JSON object with exactly these fields:
{
    \"risks\": [
        {
            \"description\": \"Specific threat or uncertainty description. Be very detailed.\",
            \"impact\": \"High/Medium/Low\",
            \"category\": \"Technical/Financial/Strategic/Stakeholder\"
        }
    ],
    \"assumptions\": \"List of critical factors considered true for planning purposes.\",
    \"constraints\": \"Real-world limitations affecting project execution.\",
    \"mitigation_ideas\": \"High-level strategic recommendations to address the primary risks.\"
}";

        $userPrompt = "Analyze project: '{$project->name}'\n";
        $userPrompt .= "Objectives: " . ($project->charter?->objectives ?? 'Not fully defined') . "\n";
        $userPrompt .= "Scope Summary: " . ($project->charter?->description ?? 'Not fully defined') . "\n";
        $userPrompt .= "Initial Assumptions: " . ($validated['assumptions'] ?? 'N/A') . "\n";
        $userPrompt .= "Initial Constraints: " . ($validated['constraints'] ?? 'N/A') . "\n";

        try {
            $aiService = new \App\Services\AiService();
            $response = $aiService->generateText($userPrompt, $systemPrompt, 1500);
            
            $cleanJson = str_replace(['```json', '```'], '', $response);
            $data = json_decode(trim($cleanJson), true);

            return response()->json([
                'success' => true,
                'analysis' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
