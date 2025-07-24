<?php

namespace App\Http\Controllers;

use App\Services\OpenAIService;
use App\Services\VectorDBService;
use App\Services\RAGService;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    private $openAIService;
    private $vectorDBService;
    private $ragService;

    public function __construct(
        OpenAIService $openAIService,
        VectorDBService $vectorDBService,
        RAGService $ragService
    ) {
        $this->openAIService = $openAIService;
        $this->vectorDBService = $vectorDBService;
        $this->ragService = $ragService;
    }

    public function completion(Request $request)
    {
        $validatedData = $request->validate([
            'helpdeskId' => 'required|integer',
            'projectName' => 'required|string',
            'messages' => 'required|array',
            'messages.*.role' => 'required|string|in:USER,AGENT',
            'messages.*.content' => 'required|string',
        ]);

        $projectName = $validatedData['projectName'];
        $userMessages = $validatedData['messages'];
        $lastUserMessage = null;

        foreach ($userMessages as $message) {
            if ($message['role'] === 'USER') {
                $lastUserMessage = $message['content'];
            }
        }

        if (!$lastUserMessage) {
            return response()->json([
                'error' => 'No user message found in the conversation'
            ], 400);
        }

        $embedding = $this->openAIService->createEmbedding($lastUserMessage);
        $relevantContent = $this->vectorDBService->search($embedding, $projectName);
        
        $handoverToHumanNeeded = false;
        foreach ($relevantContent as $content) {
            if (isset($content['type']) && $content['type'] === 'N2') {
                $handoverToHumanNeeded = true;
                break;
            }
        }
        
        $aiResponse = $this->ragService->generateResponse($userMessages, $relevantContent);
        
        $response = [
            'messages' => array_merge(
                $userMessages, 
                [
                    [
                        'role' => 'AGENT',
                        'content' => $aiResponse
                    ]
                ]
            ),
            'handoverToHumanNeeded' => $handoverToHumanNeeded,
            'sectionsRetrieved' => array_map(function($item) {
                return [
                    'score' => $item['score'],
                    'content' => $item['content']
                ];
            }, $relevantContent)
        ];

        return response()->json($response);
    }
} 