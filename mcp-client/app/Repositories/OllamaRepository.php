<?php

namespace App\Repositories;

use App\Contracts\AiServiceInterface;
use App\Services\OllamaService;

/**
 * Repository para manejar operaciones con Ollama.
 *
 * Implementa el patrón Repository para abstraer la lógica de acceso a Ollama.
 * Sigue el principio de Single Responsibility (SOLID).
 */
class OllamaRepository implements AiServiceInterface
{
    public function __construct(
        private readonly OllamaService $ollamaService
    ) {}

    public function isAvailable(): bool
    {
        return $this->ollamaService->isAvailable();
    }

    public function listModels(): array
    {
        return $this->ollamaService->listModels();
    }

    public function generate(string $prompt, array $options = []): ?string
    {
        return $this->ollamaService->generate($prompt, $options);
    }

    public function generateStreaming(string $prompt, callable $callback, array $options = []): void
    {
        $this->ollamaService->generateStreaming($prompt, $callback, $options);
    }

    public function analyzeAndDecideTool(string $userInput, array $availableTools): array
    {
        return $this->ollamaService->analyzeAndDecideTool($userInput, $availableTools);
    }
}
