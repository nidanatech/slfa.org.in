<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\BotTrainingData;
use App\Models\AiBot;
use App\Services\EmbeddingService;
use Illuminate\Support\Facades\Auth;

class BotTrainingController extends Controller
{
    public function index(AiBot $bot)
    {
        $trainingData = BotTrainingData::where('bot_id', $bot->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($trainingData);
    }

    public function store(Request $request, AiBot $bot)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        $tenant = Auth::user()->tenant;
        $apiKey = $tenant->openai_key ?? config('services.openai.key');

        $embeddingService = new EmbeddingService($apiKey);
        $embedding = $embeddingService->generate($validated['question']);

        $trainingData = BotTrainingData::create([
            'bot_id' => $bot->id,
            'tenant_id' => $tenant->id,
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'embedding' => $embedding
        ]);

        return response()->json($trainingData);
    }

    public function destroy(BotTrainingData $trainingData)
    {
        $trainingData->delete();
        return response()->json(['success' => true]);
    }
}
