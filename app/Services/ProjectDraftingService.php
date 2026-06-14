<?php

namespace App\Services;

use App\Models\Opportunity;
use App\Models\OpportunityCharter;
use App\Models\Message;
use App\Models\Task;

class ProjectDraftingService
{
    protected $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Analyzes selected messages in the context of the Opportunity's Product
     * to extract PMBOK essentials and identify missing information.
     */
    public function analyzeContext(Opportunity $opportunity, array $messageIds): array
    {
        $messagesText = Message::whereIn('id', $messageIds)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($m) => ($m->is_from_contact ? 'Client' : 'Freelancer') . ': ' . $m->body)
            ->implode("\n");

        $productContext = $opportunity->product 
            ? "Product Name: {$opportunity->product->name}\nType: {$opportunity->product->type}\nDescription: {$opportunity->product->description}"
            : "Generic Freelance Service";

        $systemPrompt = "You are an elite Business Consultant and Strategy Expert.
Your goal is to parse conversation transcripts and extract project requirements for the provided Product/Service context to create a high-fidelity, EXTENSIVE Project Authorization Document.
IMPORTANT:
1. DO NOT mention 'PMBOK' or technical project management jargon.
2. Refer to the 'Project Manager' as 'Business Lead' or use the company profile.
3. 'In Scope' and 'Out of Scope' MUST be presented as extensive, detailed, numbered lists. Each item SHOULD be on a NEW LINE with a newline character between them.
4. DO NOT provide lists as a single paragraph. Use a vertical list format.
5. Be descriptive, professional, and detailed for each section.

Return ONLY valid JSON with exactly this structure:
{
    \"extractedData\": {
        \"purpose_justification\": \"Detailed explanation of business need, strategic alignment, and the specific problem we are solving.\",
        \"objectives\": \"Extensive measurable goals with detailed Success Criteria (KPIs).\",
        \"high_level_requirements\": \"Comprehensive list of essential features, technical needs, and stakeholder expectations.\",
        \"description\": \"Professional multi-paragraph summary of the project vision and execution boundary.\",
        \"deliverables\": \"Categorized list of outputs. Use newlines between items.\",
        \"in_scope\": \"Extensive numbered list of everything included. Use a NEW LINE for each item.\",
        \"out_of_scope\": \"Extensive numbered list of explicit exclusions. Use a NEW LINE for each item.\",
        \"overall_risk\": \"In-depth analysis of primary threats, market risks, or technical hurdles.\",
        \"milestone_summary\": \"List of major phases with estimated week numbers.\",
        \"budget_summary\": \"Professional financial breakdown summary or resource requirements.\",
        \"stakeholder_list\": \"Who is involved and their role/impact.\",
        \"approval_requirements\": \"Definition of 'Done' and who has decision authority.\",
        \"pm_authority\": \"Authority level of the Business Lead.\",
        \"sponsor_authority\": \"The client sponsor's role and their ultimate authority level.\",
        \"estimated_duration_weeks\": \"integer\",
        \"budget_amount\": \"float\",
        \"currency\": \"USD/EUR/etc\",
        \"estimated_start_date\": \"YYYY-MM-DD or null\",
        \"estimated_end_date\": \"YYYY-MM-DD or null\",
        \"assumptions\": \"List of critical assumptions.\",
        \"constraints\": \"Real-world constraints.\"
    },
    \"missingInformation\": [
        \"List specific questions that must be answered by the client to fill remaining gaps.\"
    ],
    \"canDraft\": \"boolean\"
}
Product Context:
{$productContext}
";

        $response = $this->aiService->generateText("Here is the conversation transcript:\n\n{$messagesText}", $systemPrompt, 1500);

        // Clean json markup
        $response = str_replace(['```json', '```'], '', $response);
        return json_decode(trim($response), true) ?? [];
    }

    /**
     * Finalizes the AI draft and saves it to the Opportunity.
     */
    public function draftCharter(Opportunity $opportunity, array $charterData)
    {
        $charter = OpportunityCharter::updateOrCreate(
            ['opportunity_id' => $opportunity->id],
            [
                'purpose_justification' => $charterData['purpose_justification'] ?? '',
                'objectives' => $charterData['objectives'] ?? '',
                'high_level_requirements' => $charterData['high_level_requirements'] ?? '',
                'description' => $charterData['description'] ?? '',
                'deliverables' => $charterData['deliverables'] ?? '',
                'in_scope' => $charterData['in_scope'] ?? '',
                'out_of_scope' => $charterData['out_of_scope'] ?? '',
                'overall_risk' => $charterData['overall_risk'] ?? '',
                'milestone_summary' => $charterData['milestone_summary'] ?? '',
                'budget_summary' => $charterData['budget_summary'] ?? '',
                'stakeholder_list' => $charterData['stakeholder_list'] ?? '',
                'approval_requirements' => $charterData['approval_requirements'] ?? '',
                'pm_authority' => $charterData['pm_authority'] ?? '',
                'sponsor_authority' => $charterData['sponsor_authority'] ?? '',
                'estimated_duration_weeks' => $charterData['estimated_duration_weeks'] ?? null,
                'budget_amount' => $charterData['budget_amount'] ?? null,
                'currency' => $charterData['currency'] ?? 'USD',
                'estimated_start_date' => $charterData['estimated_start_date'] ?? null,
                'estimated_end_date' => $charterData['estimated_end_date'] ?? null,
                'assumptions' => $charterData['assumptions'] ?? '',
                'constraints' => $charterData['constraints'] ?? '',
                'is_signed' => false
            ]
        );

        return $charter;
    }

    /**
     * Generates Task milestones based on the approved charter.
     */
    public function generateWbs(Opportunity $opportunity)
    {
        $charter = $opportunity->charter;
        if (!$charter) return;

        $productContext = $opportunity->product 
            ? "Product Name: {$opportunity->product->name}\nType: {$opportunity->product->type}\nDescription: {$opportunity->product->description}"
            : "Generic Freelance Service";

        $systemPrompt = "You are a PMBOK-certified Project Manager.
Given the following project charter and product context, generate a Work Breakdown Structure (WBS) broken into major milestones.
Return ONLY valid JSON with the following structure:
{
    \"milestones\": [
        {
            \"name\": \"Milestone 1\",
            \"tasks\": [
                { \"title\": \"Task Title\", \"description\": \"Details\", \"estimated_hours\": 5 }
            ]
        }
    ]
}
Product Context: {$productContext}";

        $charterText = "Objectives: {$charter->objectives}\nDeliverables: {$charter->deliverables}\nIn Scope: {$charter->in_scope}";

        $response = $this->aiService->generateText("Here is the Charter:\n\n{$charterText}", $systemPrompt, 1500);
        $response = str_replace(['```json', '```'], '', $response);
        $wbsData = json_decode(trim($response), true) ?? [];

        if (!empty($wbsData['milestones'])) {
            foreach ($wbsData['milestones'] as $milestone) {
                foreach ($milestone['tasks'] as $taskData) {
                    Task::create([
                        'tenant_id' => $opportunity->tenant_id,
                        'object_type' => Opportunity::class,
                        'object_id' => $opportunity->id,
                        'title' => $taskData['title'],
                        'description' => $taskData['description'],
                        'milestone_name' => $milestone['name'],
                        'status' => 'pending',
                        'created_by' => $opportunity->owner_id ?? null,
                        'owner_id' => $opportunity->owner_id ?? null,
                    ]);
                }
            }
        }
    }
}
