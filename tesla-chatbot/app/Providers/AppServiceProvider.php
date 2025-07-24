<?php

namespace App\Providers;

use App\Services\OpenAIService;
use App\Services\VectorDBService;
use App\Services\RAGService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OpenAIService::class, function ($app) {
            return new OpenAIService();
        });

        $this->app->singleton(VectorDBService::class, function ($app) {
            return new VectorDBService();
        });

        $this->app->singleton(RAGService::class, function ($app) {
            return new RAGService(
                $app->make(OpenAIService::class)
            );
        });
    }

    public function boot(): void
    {
    }
}
