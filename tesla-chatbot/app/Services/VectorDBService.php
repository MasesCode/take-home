<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class VectorDBService
{
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('VECTOR_DB_KEY');
        $this->baseUrl = env('VECTOR_DB_URL', 'https://claudia-db.search.windows.net');
    }

    public function search(array $embedding, string $projectName, int $limit = 3)
    {
        $url = $this->baseUrl . '/indexes/claudia-ids-index-large/docs/search?api-version=2023-11-01';
        
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'api-key' => $this->apiKey,
        ])->post($url, [
            'count' => true,
            'select' => 'content, type',
            'top' => 10,
            'filter' => "projectName eq '{$projectName}'",
            'vectorQueries' => [
                [
                    'vector' => $embedding,
                    'k' => $limit,
                    'fields' => 'embeddings',
                    'kind' => 'vector',
                ]
            ],
        ]);

        if ($response->successful()) {
            $data = $response->json();
            
            $results = [];
            if (isset($data['value']) && is_array($data['value'])) {
                foreach ($data['value'] as $index => $item) {
                    $results[] = [
                        'score' => isset($data['@search.scores']) ? $data['@search.scores'][$index] : 0.0,
                        'content' => $item['content'] ?? '',
                        'type' => $item['type'] ?? 'N1',
                    ];
                }
            }
            
            return $results;
        }

        throw new \Exception('Failed to search Vector DB: ' . $response->body());
    }
} 