<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Servicio para interactuar con Ollama API.
 */
class OllamaService
{
    private Client $httpClient;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $baseUrl = 'http://localhost:11434',
        private readonly string $model = 'mistral',
    ) {
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 120,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Verifica que Ollama esté disponible.
     */
    public function isAvailable(): bool
    {
        $response = $this->httpClient->get('/api/tags');

        return $response->getStatusCode() === 200;
    }

    /**
     * Lista los modelos disponibles en Ollama.
     */
    public function listModels(): array
    {
        $response = $this->httpClient->get('/api/tags');
        $data = json_decode($response->getBody()->getContents(), true);

        return array_map(fn ($model) => $model['name'], $data['models'] ?? []);
    }

    /**
     * Genera una respuesta usando el modelo especificado.
     */
    public function generate(string $prompt, array $options = []): ?string
    {
        $payload = array_merge([
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
        ], $options);

        $this->logger->info('Generando respuesta con Ollama', [
            'model' => $this->model,
            'prompt_length' => strlen($prompt),
        ]);

        $response = $this->httpClient->post('/api/generate', [
            'json' => $payload,
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['response'] ?? null;
    }

    /**
     * Genera una respuesta con streaming.
     */
    public function generateStreaming(string $prompt, callable $callback, array $options = []): void
    {
        $payload = array_merge([
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => true,
        ], $options);

        $response = $this->httpClient->post('/api/generate', [
            'json' => $payload,
            'stream' => true,
        ]);

        $body = $response->getBody();

        while (! $body->eof()) {
            $line = $this->readLine($body);
            if ($line === null) {
                break;
            }

            $data = json_decode($line, true);
            if ($data && isset($data['response'])) {
                $callback($data['response'], $data['done'] ?? false);
            }

            if ($data['done'] ?? false) {
                break;
            }
        }
    }

    /**
     * Procesa un mensaje del usuario y decide si usar herramientas MCP.
     *
     * @param  string  $userInput  Mensaje del usuario
     * @param  array  $availableTools  Lista de herramientas MCP disponibles
     * @return array ['use_tool' => bool, 'tool' => string|null, 'arguments' => array, 'reasoning' => string]
     */
    public function analyzeAndDecideTool(string $userInput, array $availableTools): array
    {
        // Construir descripción de herramientas
        $toolDescriptions = array_map(function ($tool) {
            return "- {$tool['name']}: {$tool['description']}";
        }, $availableTools);

        $toolsText = implode("\n", $toolDescriptions);

        $prompt = <<<PROMPT
Eres un asistente inteligente. Tienes acceso a las siguientes herramientas:

{$toolsText}

Usuario: {$userInput}

Analiza el mensaje del usuario y decide:
1. ¿Necesitas usar alguna herramienta para responder?
2. Si SÍ, ¿cuál herramienta y con qué argumentos?
3. Si NO, indica que responderás directamente.

Responde ÚNICAMENTE con un JSON válido en este formato:

Si necesitas usar una herramienta:
{
  "use_tool": true,
  "tool": "nombre_de_herramienta",
  "arguments": {
    "a": número,
    "b": número
  },
  "reasoning": "explicación breve"
}

Si NO necesitas herramientas:
{
  "use_tool": false,
  "reasoning": "responderé directamente"
}

JSON:
PROMPT;

        $response = $this->generate($prompt, [
            'temperature' => 0.1,
        ]);

        if (! $response) {
            return ['use_tool' => false, 'reasoning' => 'No se pudo analizar'];
        }

        // Extraer JSON de la respuesta
        $response = trim($response);

        // Buscar JSON en la respuesta
        if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/s', $response, $matches)) {
            $json = $matches[0];
            $data = json_decode($json, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $this->logger->info('LLM decision', $data);

                return $data;
            }
        }

        $this->logger->warning('No se pudo parsear respuesta de Ollama', ['response' => $response]);

        return ['use_tool' => false, 'reasoning' => 'Error al parsear respuesta'];
    }

    /**
     * Lee una línea del stream.
     */
    private function readLine($stream): ?string
    {
        $line = '';
        while (! $stream->eof()) {
            $char = $stream->read(1);
            if ($char === "\n") {
                break;
            }
            $line .= $char;
        }

        return $line === '' ? null : $line;
    }
}
