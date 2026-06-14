<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Models\Contact;
use App\Models\Message;
use App\Models\Task;
use App\Services\AiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAutomatedTasksForChannel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Channel $channel;

    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
    }

    public function handle(AiService $aiService): void
    {
        $contacts = Contact::where('channel_id', $this->channel->id)
            ->where('task_automation_enabled', true)
            ->get();

        foreach ($contacts as $contact) {
            try {
                // Fetch last 24 hours of messages
                $messages = Message::whereHas('conversation', function ($query) use ($contact) {
                    $query->where('contact_id', $contact->id);
                })
                ->where('created_at', '>=', now()->subHours(24))
                ->orderBy('created_at', 'asc')
                ->get();

                if ($messages->isEmpty()) {
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

                $systemPrompt = "You are a professional task manager. Analyze the following conversation from the last 24 hours (which may be a group chat with multiple participants) and identify if there are any pending action items, requests, or tasks that need to be followed up on. 
                - If specific participants are responsible for a task, mention them in the task description.
                - Summarize the identified tasks into a clear, concise title and a detailed description.
                - Format your response as a JSON object with 'title' and 'description' keys. If no tasks are found, return '{\"title\": null, \"description\": null}'.";

                $response = $aiService->generateText($conversationText, $systemPrompt);
                $result = json_decode($response, true);

                if (isset($result['title']) && $result['title'] !== null) {
                    // Create Task
                    Task::create([
                        'tenant_id' => $contact->tenant_id,
                        'owner_id' => $contact->user_id ?? 1,
                        'created_by' => 1, // System
                        'title' => $result['title'],
                        'description' => $result['description'] . "\n\nGenerated from automated conversation analysis.",
                        'status' => 'pending',
                        'priority' => 'medium',
                        'due_at' => now()->addDay(),
                        'object_id' => $contact->id,
                        'object_type' => Contact::class,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("GenerateAutomatedTasksForChannel Error for contact {$contact->id}: " . $e->getMessage());
            }
        }
    }
}
