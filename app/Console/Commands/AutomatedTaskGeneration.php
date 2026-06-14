<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\Task;
use App\Models\Message;
use App\Services\AiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutomatedTaskGeneration extends Command
{
    protected $signature = 'sydkic:generate-automated-tasks';
    protected $description = 'Generate tasks from conversations for contacts with task automation enabled';

    protected AiService $aiService;

    public function __construct(AiService $aiService)
    {
        parent::__construct();
        $this->aiService = $aiService;
    }

    public function handle()
    {
        $this->info('Starting automated task generation...');

        $contacts = Contact::where('task_automation_enabled', true)->get();

        foreach ($contacts as $contact) {
            $this->info("Processing contact: {$contact->name} ({$contact->id})");

            // Fetch last 24 hours of messages
            $messages = Message::whereHas('conversation', function ($query) use ($contact) {
                $query->where('contact_id', $contact->id);
            })
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'asc')
            ->get();

            if ($messages->isEmpty()) {
                $this->info("No new messages in the last 24 hours for {$contact->name}. Skipping.");
                continue;
            }

            // Format conversation for AI
            $conversationText = $messages->map(function ($msg) {
                if ($msg->direction === 'outbound') {
                    $sender = 'Agent';
                } else {
                    $sender = $msg->meta['sender_name'] ?? 'Contact';
                }
                return "[{$msg->created_at}] {$sender}: {$msg->content}";
            })->implode("\n");

            try {
                $systemPrompt = "You are a professional task manager. Analyze the following conversation from the last 24 hours (which may be a group chat with multiple participants) and identify if there are any pending action items, requests, or tasks that need to be followed up on. 
                - If specific participants are responsible for a task, mention them in the task description.
                - Summarize the identified tasks into a clear, concise title and a detailed description.
                - Format your response as a JSON object with 'title' and 'description' keys. If no tasks are found, return '{\"title\": null, \"description\": null}'.";

                $response = $this->aiService->generateText($conversationText, $systemPrompt);
                $result = json_decode($response, true);

                if (isset($result['title']) && $result['title'] !== null) {
                    // Create Task
                    Task::create([
                        'tenant_id' => $contact->tenant_id,
                        'owner_id' => $contact->user_id ?? 1, // Fallback to first user or admin
                        'created_by' => 1, // System
                        'title' => $result['title'],
                        'description' => $result['description'] . "\n\nGenerated from automated conversation analysis.",
                        'status' => 'pending',
                        'priority' => 'medium',
                        'due_at' => now()->addDay(),
                        'object_id' => $contact->id,
                        'object_type' => Contact::class,
                    ]);

                    $this->info("Task created for {$contact->name}: {$result['title']}");
                } else {
                    $this->info("No substantial tasks identified for {$contact->name}.");
                }
            } catch (\Exception $e) {
                Log::error("AutomatedTaskGeneration Error for contact {$contact->id}: " . $e->getMessage());
                $this->error("Failed to generate task for {$contact->name}");
            }
        }

        $this->info('Automated task generation completed.');
    }
}
