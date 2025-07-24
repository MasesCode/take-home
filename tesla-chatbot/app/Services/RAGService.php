<?php

namespace App\Services;

class RAGService
{
    private $openAIService;
    private $clarificationCount;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
        $this->clarificationCount = 0;
    }

    public function generateResponse(array $messages, array $relevantContent)
    {
        $lastUserMessage = $this->getLastUserMessage($messages);
        $needsClarification = $this->needsClarification($lastUserMessage, $relevantContent);
        
        if ($needsClarification) {
            $this->clarificationCount++;
            
            if ($this->clarificationCount >= 3) {
                return "I need more information to help you properly, but I've reached my clarification limit. Let me connect you with a human specialist who can assist you better.";
            }
            
            return $this->generateClarificationQuestion($lastUserMessage);
        }

        $systemPrompt = $this->buildSystemPrompt($relevantContent);
        return $this->openAIService->createChatCompletion($messages, $systemPrompt);
    }

    private function getLastUserMessage(array $messages)
    {
        $lastUserMessage = null;
        foreach ($messages as $message) {
            if ($message['role'] === 'USER') {
                $lastUserMessage = $message['content'];
            }
        }
        return $lastUserMessage;
    }

    private function needsClarification($userMessage, array $relevantContent)
    {
        if (empty($relevantContent)) {
            return true;
        }

        $highestScore = 0;
        foreach ($relevantContent as $content) {
            if (isset($content['score']) && $content['score'] > $highestScore) {
                $highestScore = $content['score'];
            }
        }

        return $highestScore < 0.5;
    }

    private function generateClarificationQuestion($userMessage)
    {
        $systemPrompt = "You are Claudia, Tesla's support assistant. The user has asked a question, but it's not clear or specific enough for you to provide a good answer. Generate a polite question to ask the user for clarification. Make your question specific to what was asked, not generic.";
        
        $messages = [
            [
                'role' => 'USER',
                'content' => $userMessage
            ]
        ];
        
        return $this->openAIService->createChatCompletion($messages, $systemPrompt);
    }

    private function buildSystemPrompt(array $relevantContent)
    {
        $contextString = "";
        foreach ($relevantContent as $content) {
            if (isset($content['content'])) {
                $contextString .= $content['content'] . "\n\n";
            }
        }

        return <<<EOT
You are Claudia, Tesla's support assistant. You are friendly, helpful, and knowledgeable about Tesla products and services.

IMPORTANT RULES:
1. ONLY use information from the context provided below to answer the question.
2. If you don't know the answer based on the provided context, politely say you don't have that information and offer to connect the user with a human specialist.
3. Do not make up information or use prior knowledge about Tesla.
4. Your responses should be conversational, helpful, and professional.
5. Always sign your response with "Claudia, Tesla Support Assistant 😊"

CONTEXT:
$contextString
EOT;
    }
} 