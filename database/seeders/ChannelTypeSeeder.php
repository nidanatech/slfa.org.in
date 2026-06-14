<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChannelType;

class ChannelTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['id' => 'messenger', 'name' => 'Messenger', 'icon' => 'Facebook'],
            ['id' => 'instagram', 'name' => 'Instagram', 'icon' => 'Instagram'],
            ['id' => 'whatsapp_api', 'name' => 'WhatsApp API', 'icon' => 'MessageCircle'],
            ['id' => 'whatsapp', 'name' => 'WhatsApp', 'icon' => 'MessageCircle'],
            ['id' => 'web', 'name' => 'Web Chat', 'icon' => 'Globe'],
            ['id' => 'wordpress', 'name' => 'WordPress', 'icon' => 'Globe'],
            ['id' => 'lead_form', 'name' => 'Lead Forms', 'icon' => 'FileText'],
            ['id' => 'api', 'name' => 'Custom API', 'icon' => 'Code'],
            ['id' => 'gmail', 'name' => 'Gmail', 'icon' => 'Mail'],
            ['id' => 'outlook', 'name' => 'Outlook', 'icon' => 'Mail'],
            ['id' => 'ses', 'name' => 'Amazon SES', 'icon' => 'Zap'],
            ['id' => 'linkedin', 'name' => 'LinkedIn', 'icon' => 'Linkedin'],
            ['id' => 'tiktok', 'name' => 'TikTok', 'icon' => 'Video'],
            ['id' => 'youtube', 'name' => 'YouTube', 'icon' => 'Youtube'],
            ['id' => 'google_business', 'name' => 'Google Business', 'icon' => 'Store'],
        ];

        foreach ($types as $type) {
            ChannelType::updateOrCreate(['id' => $type['id']], $type);
        }
    }
}
