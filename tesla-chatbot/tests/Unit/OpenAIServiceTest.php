<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\Http;
use Mockery;

class OpenAIServiceTest extends TestCase
{
    protected $openAIService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->openAIService = new OpenAIService();
    }

    public function testCreateEmbedding()
    {
        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response([
                'data' => [
                    [
                        'embedding' => [0.1, 0.2, 0.3]
                    ]
                ]
            ], 200)
        ]);

        $embedding = $this->openAIService->createEmbedding('Test text');
        
        $this->assertIsArray($embedding);
        $this->assertEquals([0.1, 0.2, 0.3], $embedding);
    }

    public function testCreateChatCompletion()
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Test response'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $messages = [
            [
                'role' => 'USER',
                'content' => 'Test question'
            ]
        ];

        $response = $this->openAIService->createChatCompletion($messages, 'You are a helpful assistant');
        
        $this->assertIsString($response);
        $this->assertEquals('Test response', $response);
    }

    public function testCreateEmbeddingFailure()
    {
        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response([
                'error' => 'API Error'
            ], 500)
        ]);

        $this->expectException(\Exception::class);
        
        $this->openAIService->createEmbedding('Test text');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
