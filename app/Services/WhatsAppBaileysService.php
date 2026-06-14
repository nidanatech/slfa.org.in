<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppBaileysService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.whatsapp_baileys.url', 'http://localhost:3001');
    }

    public function getQrCode(string $sessionId)
    {
        try {
            $response = Http::get("{$this->baseUrl}/qr/{$sessionId}");
            return $response->json();
        } catch (\Exception $e) {
            Log::error("WhatsApp Baileys Service Error: " . $e->getMessage());
            return ['error' => 'Service unavailable'];
        }
    }

    public function getStatus(string $sessionId)
    {
        try {
            $response = Http::get("{$this->baseUrl}/status/{$sessionId}");
            return $response->json();
        } catch (\Exception $e) {
            Log::error("WhatsApp Baileys Service Error: " . $e->getMessage());
            return ['status' => 'disconnected', 'error' => 'Service unavailable'];
        }
    }

    public function sendMessage(string $sessionId, string $to, string $text)
    {
        try {
            // Baileys expects jid (e.g. 123456789@s.whatsapp.net)
            $jid = str_contains($to, '@') ? $to : $to . '@s.whatsapp.net';
            
            $response = Http::post("{$this->baseUrl}/send/{$sessionId}", [
                'jid' => $jid,
                'content' => ['text' => $text]
            ]);
            
            return $response->json();
        } catch (\Exception $e) {
            Log::error("WhatsApp Baileys Service Error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Service unavailable'];
        }
    }

    public function logout(string $sessionId)
    {
        try {
            $response = Http::post("{$this->baseUrl}/logout/{$sessionId}");
            return $response->json();
        } catch (\Exception $e) {
            Log::error("WhatsApp Baileys Service Error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Service unavailable'];
        }
    }

    public function syncAll(string $sessionId)
    {
        try {
            $response = Http::post("{$this->baseUrl}/sync/{$sessionId}");
            return $response->json();
        } catch (\Exception $e) {
            Log::error("WhatsApp Baileys Service Error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Service unavailable'];
        }
    }
}
