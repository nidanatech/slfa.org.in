<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    private ?string $apiKey = null;
    private string $model = 'text-embedding-3-small';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.openai.key');
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * Generate embedding for a single text
     */
    public function generate(string $text): array
    {
        return $this->generateBatch([$text])[0];
    }

    /**
     * Generate embeddings for multiple texts in batch
     * Max 100 texts per batch recommended
     */
    public function generateBatch(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }

        if (empty($this->apiKey)) {
            throw new \Exception('OpenAI API key not configured');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/embeddings', [
                'model' => $this->model,
                'input' => $texts,
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI Embedding API Error: ' . $response->body());
                throw new \Exception('Failed to generate embeddings: ' . $response->status());
            }

            $data = $response->json();
            
            // Extract embeddings from response
            $embeddings = [];
            foreach ($data['data'] as $item) {
                $embeddings[] = $item['embedding'];
            }

            return $embeddings;

        } catch (\Exception $e) {
            Log::error('Embedding generation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate cosine similarity between two vectors
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            throw new \InvalidArgumentException('Vectors must have same dimensions');
        }

        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;

        for ($i = 0; $i < count($a); $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $magnitudeA += $a[$i] * $a[$i];
            $magnitudeB += $b[$i] * $b[$i];
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        if ($magnitudeA == 0 || $magnitudeB == 0) {
            return 0;
        }

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }
}
