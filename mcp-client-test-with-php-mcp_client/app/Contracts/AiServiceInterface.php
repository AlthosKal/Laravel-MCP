<?php

namespace App\Contracts;

/**
 * Interface AiServiceInterface
 *
 * Define el contrato para interactuar con servicios de IA (como Ollama).
 * Siguiendo el principio de Inversión de Dependencias (SOLID).
 */
interface AiServiceInterface
{
    /**
     * Verifica si el servicio está disponible.
     */
    public function isAvailable(): bool;

    /**
     * Lista los modelos disponibles.
     */
    public function listModels(): array;

    /**
     * Genera una respuesta a partir de un prompt.
     */
    public function generate(string $prompt, array $options = []): ?string;

    /**
     * Genera una respuesta con streaming.
     */
    public function generateStreaming(string $prompt, callable $callback, array $options = []): void;

    /**
     * Analiza el mensaje del usuario y decide si usar herramientas MCP.
     */
    public function analyzeAndDecideTool(string $userInput, array $availableTools): array;
}
