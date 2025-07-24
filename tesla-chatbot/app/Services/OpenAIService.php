<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAIService
{
    private $apiKey;
    private $embeddingModel;
    private $completionModel;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
        $this->embeddingModel = 'text-embedding-3-large';
        $this->completionModel = 'gpt-4o';
    }

    public function createEmbedding($text)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/embeddings', [
            'model' => $this->embeddingModel,
            'input' => $text,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['data'][0]['embedding'] ?? null;
        }

        throw new \Exception('Failed to create embedding: ' . $response->body());
    }

    public function createChatCompletion($messages, $systemPrompt = null)
    {
        $formattedMessages = [];
        
        if ($systemPrompt) {
            $formattedMessages[] = [
                'role' => 'system',
                'content' => $systemPrompt
            ];
        }

        foreach ($messages as $message) {
            $role = strtolower($message['role']) === 'user' ? 'user' : 'assistant';
            $formattedMessages[] = [
                'role' => $role,
                'content' => $message['content']
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->completionModel,
            'messages' => $formattedMessages,
            'temperature' => 0.7,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? null;
        }

        throw new \Exception('Failed to create chat completion: ' . $response->body());
    }
} 