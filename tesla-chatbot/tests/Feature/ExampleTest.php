<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_api_endpoint_returns_a_successful_response(): void
    {
        $response = $this->postJson('/api/conversations/completions', [
            'helpdeskId' => 123456,
            'projectName' => 'tesla_motors',
            'messages' => [
                [
                    'role' => 'USER',
                    'content' => 'Test message'
                ]
            ]
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'messages',
                    'handoverToHumanNeeded',
                    'sectionsRetrieved'
                ]);
    }
}
