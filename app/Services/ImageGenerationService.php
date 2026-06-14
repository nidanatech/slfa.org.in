<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ImageGenerationService
{
    private string $apiKey;

    public function __construct(string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.openai.key');
    }

    /**
     * Generate image using specified model
     */
    public function generate(string $prompt, string $model = 'dall-e-3', string $quality = 'standard', string $size = '1024x1024'): string
    {
        return match($model) {
            'dall-e-3', 'dall-e-2' => $this->generateWithDallE($prompt, $model, $quality, $size),
            'imagen' => $this->generateWithImagen($prompt, $quality, $size),
            'stable-diffusion' => $this->generateWithStableDiffusion($prompt, $quality, $size),
            'midjourney' => $this->generateWithMidjourney($prompt, $quality, $size),
            default => throw new \Exception("Unsupported image model: {$model}"),
        };
    }

    /**
     * Generate with DALL-E
     */
    private function generateWithDallE(string $prompt, string $model, string $quality, string $size): string
    {
        // DALL-E only supports specific sizes, map to closest
        $dalleSizes = ['1024x1024', '1792x1024', '1024x1792'];
        $mappedSize = $this->mapToDallESize($size);

        $response = Http::withToken($this->apiKey)
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => $model,
                'prompt' => $prompt,
                'n' => 1,
                'size' => $mappedSize,
                'quality' => $quality,
            ]);

        if ($response->successful()) {
            $imageUrl = $response->json()['data'][0]['url'];
            return $this->downloadAndStore($imageUrl);
        }

        throw new \Exception('DALL-E image generation failed: ' . $response->body());
    }

    /**
     * Map custom size to DALL-E supported size
     */
    private function mapToDallESize(string $size): string
    {
        // Parse dimensions
        [$width, $height] = explode('x', $size);
        
        // Determine aspect ratio
        if ($width == $height) {
            return '1024x1024'; // Square
        } elseif ($width > $height) {
            return '1792x1024'; // Landscape
        } else {
            return '1024x1792'; // Portrait
        }
    }

    /**
     * Generate with Google Imagen
     */
    private function generateWithImagen(string $prompt, string $quality, string $size): string
    {
        [$width, $height] = explode('x', $size);
        
        // Google Imagen API implementation
        $endpoint = 'https://us-central1-aiplatform.googleapis.com/v1/projects/YOUR_PROJECT/locations/us-central1/publishers/google/models/imagegeneration:predict';
        
        $response = Http::withToken($this->apiKey)
            ->post($endpoint, [
                'instances' => [
                    ['prompt' => $prompt]
                ],
                'parameters' => [
                    'sampleCount' => 1,
                    'aspectRatio' => $this->getAspectRatio($width, $height),
                ],
            ]);

        if ($response->successful()) {
            $imageData = $response->json()['predictions'][0]['bytesBase64Encoded'];
            return $this->storeBase64Image($imageData);
        }

        throw new \Exception('Imagen generation failed');
    }

    /**
     * Generate with Stable Diffusion
     */
    private function generateWithStableDiffusion(string $prompt, string $quality, string $size): string
    {
        [$width, $height] = explode('x', $size);
        
        // Stability AI API implementation
        $endpoint = 'https://api.stability.ai/v1/generation/stable-diffusion-xl-1024-v1-0/text-to-image';
        
        $response = Http::withToken($this->apiKey)
            ->post($endpoint, [
                'text_prompts' => [
                    ['text' => $prompt, 'weight' => 1]
                ],
                'cfg_scale' => 7,
                'height' => (int)$height,
                'width' => (int)$width,
                'samples' => 1,
                'steps' => $quality === 'hd' ? 50 : 30,
            ]);

        if ($response->successful()) {
            $imageData = $response->json()['artifacts'][0]['base64'];
            return $this->storeBase64Image($imageData);
        }

        throw new \Exception('Stable Diffusion generation failed');
    }

    /**
     * Generate with Midjourney (via API proxy)
     */
    private function generateWithMidjourney(string $prompt, string $quality, string $size): string
    {
        // Midjourney doesn't have an official API
        throw new \Exception('Midjourney integration requires a proxy service');
    }

    /**
     * Get aspect ratio string from dimensions
     */
    private function getAspectRatio(int $width, int $height): string
    {
        if ($width == $height) return '1:1';
        if ($width > $height) return '16:9';
        return '9:16';
    }

    /**
     * Download image from URL and store locally
     */
    private function downloadAndStore(string $url): string
    {
        $contents = file_get_contents($url);
        $filename = 'ai-generated/' . uniqid() . '.png';
        Storage::disk('public')->put($filename, $contents);
        
        return Storage::disk('public')->url($filename);
    }

    /**
     * Store base64 encoded image
     */
    private function storeBase64Image(string $base64Data): string
    {
        $imageData = base64_decode($base64Data);
        $filename = 'ai-generated/' . uniqid() . '.png';
        Storage::disk('public')->put($filename, $imageData);
        
        return Storage::disk('public')->url($filename);
    }
}
