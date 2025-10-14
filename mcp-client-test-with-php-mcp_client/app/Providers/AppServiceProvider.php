<?php

namespace App\Providers;

use App\Contracts\AiServiceInterface;
use App\Contracts\McpClientInterface;
use App\Repositories\McpClientRepository;
use App\Repositories\OllamaRepository;
use App\Services\McpCalculatorClient;
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

        // Bind McpClientInterface -> McpClientRepository (SINGLETON)
        // Usamos singleton para evitar múltiples conexiones STDIO simultáneas
        $this->app->singleton(McpClientInterface::class, function ($app) {
            // Por defecto usar STDIO, pero puede configurarse
            $serverType = config('services.mcp.calculator.type', 'stdio');

            $mcpClient = new McpCalculatorClient(
                logger: $app->make(LoggerInterface::class),
                serverType: $serverType,
                serverUrl: config('services.mcp.calculator.http_url', 'http://localhost:8000/mcp/calculator'),
                serverCommand: config('services.mcp.calculator.command', 'php'),
                serverArgs: config('services.mcp.calculator.args', [
                    base_path('../mcp-server-test-with-laravel_mcp/artisan'),
                    'mcp:start',
                    'calculator',
                ]),
            );

            return new McpClientRepository($mcpClient);
        });

        // ==========================================
        // Servicios singleton (para mantener estado)
        // ==========================================

        // Registrar cliente MCP HTTP
        $this->app->singleton('mcp.calculator.http', function ($app) {
            return new McpCalculatorClient(
                logger: $app->make(LoggerInterface::class),
                serverType: 'http',
                serverUrl: config('services.mcp.calculator.http_url', 'http://localhost:8000/mcp/calculator'),
            );
        });

        // Registrar cliente MCP STDIO
        $this->app->singleton('mcp.calculator.stdio', function ($app) {
            return new McpCalculatorClient(
                logger: $app->make(LoggerInterface::class),
                serverType: 'stdio',
                serverCommand: config('services.mcp.calculator.command', 'php'),
                serverArgs: config('services.mcp.calculator.args', [
                    base_path('../mcp-server-test-with-laravel_mcp/artisan'),
                    'mcp:start',
                    'calculator',
                ]),
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
