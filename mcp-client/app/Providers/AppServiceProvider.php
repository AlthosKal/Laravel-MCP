<?php

namespace App\Providers;

use App\Contracts\AiServiceInterface;
use App\Contracts\McpClientInterface;
use App\Repositories\McpClientRepository;
use App\Repositories\OllamaRepository;
use App\Services\OllamaService;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ==========================================
        // Binding de interfaces (Dependency Inversion Principle)
        // ==========================================

        // Bind AiServiceInterface -> OllamaRepository
        $this->app->bind(AiServiceInterface::class, function ($app) {
            $ollamaService = new OllamaService(
                logger: $app->make(LoggerInterface::class),
                baseUrl: config('services.ollama.base_url', 'http://localhost:11434'),
                model: config('services.ollama.model', 'mistral'),
            );

            return new OllamaRepository($ollamaService);
        });

        // Bind McpClientInterface -> McpClientRepository (STUB)
        // Este es un stub ya que no hay cliente MCP genérico configurado.
        // Para operaciones RAG, usa McpRagClientService directamente.
        $this->app->singleton(McpClientInterface::class, function ($app) {
            return new McpClientRepository(
                logger: $app->make(LoggerInterface::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
