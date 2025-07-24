<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\ConversationController;
use App\Services\OpenAIService;
use App\Services\VectorDBService;
use App\Services\RAGService;
use Illuminate\Http\Request;
use Mockery;

class ConversationControllerTest extends TestCase
{
    protected $openAIService;
    protected $vectorDBService;
    protected $ragService;
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->openAIService = Mockery::mock(OpenAIService::class);
        $this->vectorDBService = Mockery::mock(VectorDBService::class);
        $this->ragService = Mockery::mock(RAGService::class);
        
        $this->controller = new ConversationController(
            $this->openAIService,
            $this->vectorDBService,
            $this->ragService
        );
    }

    public function testCompletionWithValidRequest()
    {
        $request = new Request();
        $request->merge([
            'helpdeskId' => 123456,
            'projectName' => 'tesla_motors',
            'messages' => [
                [
                    'role' => 'USER',
                    'content' => 'How long does a Tesla battery last?'
                ]
            ]
        ]);

        $embedding = [0.1, 0.2, 0.3];
        $vectorDBResults = [
            [
                'content' => 'Tesla batteries last many years',
                'type' => 'N1',
                'score' => 0.95
            ]
        ];

        $this->openAIService->shouldReceive('createEmbedding')
            ->once()
            ->andReturn($embedding);

        $this->vectorDBService->shouldReceive('search')
            ->once()
            ->with($embedding, 'tesla_motors')
            ->andReturn($vectorDBResults);

        $this->ragService->shouldReceive('generateResponse')
            ->once()
            ->andReturn([
                'response' => 'Tesla batteries are designed to last many years.',
                'handoverToHumanNeeded' => false
            ]);

        $response = $this->controller->completion($request);
        
        $this->assertEquals(200, $response->status());
        $this->assertArrayHasKey('messages', $response->getData(true));
        $this->assertArrayHasKey('handoverToHumanNeeded', $response->getData(true));
    }

    public function testCompletionWithInvalidRequest()
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('The messages field is required.');

        $request = new Request();
        $request->merge([
            'helpdeskId' => 123456,
            'projectName' => 'tesla_motors'
            // Missing messages field should fail validation
        ]);

        $this->controller->completion($request);
    }

    public function testCompletionWithNoUserMessage()
    {
        $request = new Request();
        $request->merge([
            'helpdeskId' => 123456,
            'projectName' => 'tesla_motors',
            'messages' => [
                [
                    'role' => 'AGENT',
                    'content' => 'Hello!'
                ]
            ]
        ]);

        $response = $this->controller->completion($request);
        
        $this->assertEquals(400, $response->status());
        $this->assertEquals(
            'No user message found in the conversation',
            $response->getData(true)['error']
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
