<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AiBotResourceSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('is_system_user', true)->first() ?: User::first();
        
        $title = 'The Ultimate Guide to Setting Up Your AI Assistant';
        $slug = Str::slug($title);

        $content = <<<'EOD'
# The Ultimate Guide to Setting Up Your AI Assistant

AI Assistants are the core of automated customer engagement in Sydkic. This guide provides an exhaustive breakdown of every setting, strategy, and advanced feature available to help you build a high-performing digital agent.

---

## Phase 1: Knowledge Base Engineering
Your AI is only as smart as the data you give it. This process, known as **Retrieval-Augmented Generation (RAG)**, ensures the bot never makes things up ("hallucinates") and only speaks from your facts.

### 1. Website Scraper (Dynamic Training)
*   **What it does**: Crawls a public URL and extracts all visible text.
*   **Best Use Case**: Use this for your "About Us," "Services," and "Pricing" pages.
*   **Pro Tip**: If your website has complex navigation, add individual sub-page URLs for higher accuracy instead of just the home page.

### 2. Document Upload (The Archive)
*   **Supported Formats**: PDF, DOCX, TXT, CSV.
*   **Best Use Case**: Employee handbooks, technical manifests, service catalogs, and complex legal terms.
*   **Optimization**: Ensure your PDFs are "searchable" (not scanned images of text). The AI needs to be able to "read" the characters.

### 3. Manual Q&A Pairs (Precision Tuning)
*   **What it does**: Provides direct "If user asks X, Answer Y" logic.
*   **Best Use Case**: Handling common customer friction points, specific discount codes, or nuanced policy questions that aren't clearly written in documents.

---

## Phase 2: Persona & Intelligence settings
This section defines the "personality" and "voice" of your robot worker.

### 1. Tone Selection
Choose a tone that matches your brand identity:
*   **Professional**: Formal, concise, and results-oriented.
*   **Empathetic**: Validating, warm, and highly supportive.
*   **Casual**: Uses contractions, friendly, and relatable.
*   **Formal**: Strict adherence to grammar, no slang, high authority.

### 2. Personality Bio
This is where you "program" the AI's character. 
*   *Example*: "You are 'Syd', the friendly concierge for Green Valley Solar. You are an expert in solar panel technical specs and local rebates in California."

### 3. Intent & Primary Mission
What is the ONE thing the bot should always try to do?
*   *Lead Gen*: "Always try to collect the user's phone number."
*   *Support*: "Focus on resolving the issue using the knowledge base."
*   *Sales*: "Guide the user to book a consultation."

---

## Phase 3: Global Bot Configuration
Once the brain and personality are set, configure how the bot interacts with your system.

### 1. Primary Bot Toggle
If this is ON, the bot will automatically "claim" any new conversation entering your inbox from any platform (Instagram, FB, Email, etc.). Only one bot per business should usually be Primary.

### 2. Supported Channels
Select which connected accounts this bot should work on. You might want a "Support Bot" for Email and a "Casual Sales Bot" for Instagram.

### 4. Sleep on Manual Message
**CRITICAL**: If this is enabled, the AI will stop responding as soon as a human agent (you or your team) sends a message in the chat. This prevents the bot from "fighting" with the human agent.

---

## Phase 4: Advanced Skills (Actions)
"Skills" allow your bot to actually *do* things beyond just talking.

### Appointment Booking
*   **Connection**: Requires a Sydkic Calendar.
*   **Logic**: The bot checks your real-time availability and presents slots to the user in a natural conversation.
*   **Follow-up**: Once booked, it can automatically create an "Opportunity" in your CRM.

### Human Handover
*   **Trigger**: Can be triggered if the user asks for a human, expresses frustration, or if the bot's confidence falls below a threshold.
*   **Notification**: Sydkic will send an "Urgent" alert to your dashboard and mobile app.

### CRM Data Collection
*   **Mapping**: The bot can extract fields like "Email," "Phone," or even custom fields like "Move-in Date."
*   **Effect**: The contact record is updated in real-time, allowing you to trigger further marketing automations.

### Workflow Triggers
*   **Logic**: If the conversation reaches a certain state (e.g., "User confirmed interest"), the bot can trigger a "Workflow."
*   **Usage**: Send a welcome PDF, alert a specific department via Slack, or move a lead to a new CRM stage.

---

## Phase 5: Testing & Iteration
Never go live without testing!

1.  **Preview Panel**: Use the chat window on the right of the editor to test your knowledge base. Ask it tough questions!
2.  **Analytics**: Monitor the "Handover Rate" in your dashboard. If it's too high, your Knowledge Base might need more details.
3.  **Refinement**: AI is iterative. Most successful bots are updated weekly based on real customer feedback.
EOD;

        Post::updateOrCreate([
            'slug' => $slug,
            'type' => Post::TYPE_RESOURCE,
        ], [
            'title' => $title,
            'content' => $content,
            'excerpt' => 'A comprehensive guide to building, training, and deploying high-performance AI Assistants on the Sydkic platform.',
            'category' => 'AI & Automation',
            'status' => Post::STATUS_PUBLISHED,
            'author_id' => $author->id,
            'published_at' => now(),
        ]);
    }
}
