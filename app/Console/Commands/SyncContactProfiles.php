<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contact;
use App\Models\Channel;
use App\Services\FacebookService;
use App\Services\InstagramService;
use Illuminate\Support\Facades\Log;

class SyncContactProfiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inbox:sync-contact-profiles {--tenant= : Filter by tenant ID} {--channel= : Filter by channel ID} {--contact= : Filter by contact ID} {--force : Update even if not a placeholder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync contact profiles (names and avatars) from Facebook/Instagram APIs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting contact profile sync...');

        $query = Contact::query();

        if ($this->option('tenant')) {
            $query->where('tenant_id', $this->option('tenant'));
        }

        if ($this->option('channel')) {
            $query->where('channel_id', $this->option('channel'));
        }

        if ($this->option('contact')) {
            $query->where('id', $this->option('contact'));
        }

        if (!$this->option('force')) {
            $query->where(function ($q) {
                $q->where('name', 'Facebook User')
                  ->orWhere('name', 'Instagram User')
                  ->orWhere('name', 'unknown')
                  ->orWhere('name', 'Instagram Business Account')
                  ->orWhereNull('avatar_url');
            });
        }

        $contacts = $query->with('channel')->get();

        if ($contacts->isEmpty()) {
            $this->info('No contacts found to sync.');
            return;
        }

        $this->info("Found {$contacts->count()} contacts to sync.");

        $bar = $this->output->createProgressBar($contacts->count());
        $bar->start();

        foreach ($contacts as $contact) {
            $this->syncProfile($contact);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Sync complete!');
    }

    /**
     * Sync a single contact profile
     */
    protected function syncProfile(Contact $contact)
    {
        $channel = $contact->channel;

        if (!$channel || !$channel->is_active) {
            return;
        }

        try {
            $data = null;

            if ($channel->type === 'messenger' || $channel->type === 'facebook') {
                $service = new FacebookService($channel);
                $data = $service->getUserProfile($contact->external_id);
            } elseif ($channel->type === 'instagram') {
                $service = new InstagramService($channel);
                $data = $service->getUserInfo($contact->external_id);
            }

            if ($data) {
                $this->updateContact($contact, $data, $channel->type);
            }
        } catch (\Exception $e) {
            Log::error("Failed to sync profile for contact ID {$contact->id}: " . $e->getMessage());
        }
    }

    /**
     * Update contact with retrieved data
     */
    protected function updateContact(Contact $contact, array $data, string $platform)
    {
        $updates = [];
        $placeholders = ['Facebook User', 'Instagram User', 'unknown', 'Instagram Business Account'];

        // Extract name and avatar based on platform
        $name = $data['name'] ?? null;
        $profilePic = null;

        if ($platform === 'messenger' || $platform === 'facebook') {
            $profilePic = $data['profile_pic'] ?? null;
        } elseif ($platform === 'instagram') {
            $profilePic = $data['profile_picture_url'] ?? $data['profile_pic'] ?? null;
        }

        if ($name && (in_array($contact->name, $placeholders) || $this->option('force'))) {
            $updates['name'] = $name;
            $nameParts = Contact::splitName($name);
            $updates['first_name'] = $data['first_name'] ?? $nameParts['first_name'];
            $updates['last_name'] = $data['last_name'] ?? $nameParts['last_name'];
        }

        if ($profilePic && ($contact->avatar_url !== $profilePic || $this->option('force'))) {
            $updates['avatar_url'] = $profilePic;
        }

        // Capture username if available (Instagram)
        if (isset($data['username'])) {
            $meta = $contact->meta ?? [];
            $meta['username'] = $data['username'];
            $updates['meta'] = $meta;
        }

        if (!empty($updates)) {
            $contact->update($updates);
        }
    }
}
