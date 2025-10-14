<?php

namespace App\Services;

use App\Contracts\AiServiceInterface;
use App\Contracts\McpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio principal del Asistente IA.
 *
 * Implementa el patrón Facade para combinar funcionalidades de Ollama y MCP.
 * Coordina la interacción entre el modelo de IA y las herramientas MCP.
 * Sigue los principios SOLID: SRP, OCP, DIP.
 */
class AiAssistantService
{
    public function __construct(
        private readonly AiServiceInterface $aiService,
        private readonly McpClientInterface $mcpClient,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Procesa un mensaje del usuario y genera una respuesta.
     *
     * Este método coordina:
     * 1. Análisis del mensaje con Ollama
     * 2. Ejecución de herramientas MCP si es necesario
     * 3. Generación de respuesta final
     */
    public function processMessage(string $userMessage): array
    {
        $this->logger->info('Procesando mensaje del usuario', ['message' => $userMessage]);

        $mcpConnected = false;

        try {
            // Verificar disponibilidad de servicios
            if (!$this->aiService->isAvailable()) {
                return $this->errorResponse('El servicio de IA no está disponible');
            }

            // Obtener herramientas disponibles
            $tools = $this->getAvailableTools();

            // PASO 1: El LLM analiza y decide si usar herramientas
            $decision = $this->aiService->analyzeAndDecideTool($userMessage, $tools);

            $this->logger->info('LLM tomó decisión', $decision);

            // PASO 2: Si el LLM decide usar herramienta, ejecutarla
            if ($decision['use_tool'] ?? false) {
                // Conectar MCP
                if (!$this->mcpClient->isReady()) {
                    $this->mcpClient->connect();
                    $mcpConnected = true;
                }

                $toolName = $decision['tool'] ?? null;
                $arguments = $decision['arguments'] ?? [];

                if ($toolName && !empty($arguments)) {
                    $mcpResult = $this->mcpClient->callTool($toolName, $arguments);

                    // Desconectar MCP
                    if ($mcpConnected) {
                        $this->mcpClient->disconnect();
                    }

                    return [
                        'type' => 'assistant',
                        'content' => $this->formatToolResponse($mcpResult, $userMessage),
                        'metadata' => [
                            'tool_used' => $toolName,
                            'arguments' => $arguments,
                            'reasoning' => $decision['reasoning'] ?? '',
                        ]
                    ];
                }
            }

            // PASO 3: Si no usa herramientas, generar respuesta conversacional
            return $this->generateConversationalResponse($userMessage);

        } catch (\Throwable $e) {
            $this->logger->error('Error procesando mensaje', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Asegurar desconexión en caso de error
            if ($mcpConnected) {
                try {
                    $this->mcpClient->disconnect();
                } catch (\Throwable $disconnectError) {
                    // Ignorar errores de desconexión
                }
            }

            return $this->errorResponse('Ocurrió un error al procesar tu mensaje: ' . $e->getMessage());
        }
    }

    /**
     * Procesa un mensaje con respuesta en streaming.
     */
    public function processMessageStreaming(string $userMessage, callable $onChunk): void
    {
        $mcpConnected = false;

        try {
            // Verificar disponibilidad
            if (!$this->aiService->isAvailable()) {
                $onChunk(['error' => 'El servicio de IA no está disponible'], true);
                return;
            }

            // Obtener herramientas disponibles
            $tools = $this->getAvailableTools();

            // PASO 1: El LLM analiza y decide si usar herramientas
            $decision = $this->aiService->analyzeAndDecideTool($userMessage, $tools);

            $this->logger->info('LLM tomó decisión (streaming)', $decision);

            // PASO 2: Si el LLM decide usar herramienta, ejecutarla
            if ($decision['use_tool'] ?? false) {
                // Conectar MCP
                if (!$this->mcpClient->isReady()) {
                    $this->mcpClient->connect();
                    $mcpConnected = true;
                }

                $toolName = $decision['tool'] ?? null;
                $arguments = $decision['arguments'] ?? [];

                if ($toolName && !empty($arguments)) {
                    $mcpResult = $this->mcpClient->callTool($toolName, $arguments);

                    // Desconectar MCP
                    if ($mcpConnected) {
                        $this->mcpClient->disconnect();
                    }

                    $onChunk([
                        'type' => 'assistant',
                        'content' => $this->formatToolResponse($mcpResult, $userMessage),
                        'metadata' => [
                            'tool_used' => $toolName,
                            'arguments' => $arguments,
                            'reasoning' => $decision['reasoning'] ?? '',
                        ]
                    ], true);

                    return;
                }
            }

            // Respuesta conversacional con streaming
            $fullResponse = '';

            $this->aiService->generateStreaming(
                $this->buildConversationalPrompt($userMessage),
                function (string $chunk, bool $done) use (&$fullResponse, $onChunk) {
                    $fullResponse .= $chunk;
                    $onChunk([
                        'type' => 'assistant',
                        'content' => $chunk,
                        'full_content' => $fullResponse,
                        'done' => $done
                    ], $done);
                }
            );

        } catch (\Throwable $e) {
            $this->logger->error('Error en streaming', ['error' => $e->getMessage()]);

            // Asegurar desconexión en caso de error
            if ($mcpConnected) {
                try {
                    $this->mcpClient->disconnect();
                } catch (\Throwable $disconnectError) {
                    // Ignorar errores de desconexión
                }
            }

            $onChunk(['error' => $e->getMessage()], true);
        }
    }

    /**
     * Obtiene las herramientas disponibles.
     */
    public function getAvailableTools(): array
    {
        try {
            // Conectar temporalmente si no está conectado
            $wasConnected = $this->mcpClient->isReady();

            if (!$wasConnected) {
                $this->mcpClient->connect();
            }

            $tools = $this->mcpClient->listTools();

            // Desconectar si no estaba conectado previamente
            // Esto evita que la conexión STDIO se quede colgada
            if (!$wasConnected) {
                $this->mcpClient->disconnect();
            }

            return $tools;
        } catch (\Throwable $e) {
            $this->logger->error('Error obteniendo herramientas', ['error' => $e->getMessage()]);

            // Intentar desconectar en caso de error
            try {
                $this->mcpClient->disconnect();
            } catch (\Throwable $disconnectError) {
                // Ignorar errores de desconexión
            }

            return [];
        }
    }

    /**
     * Formatea la respuesta de una herramienta MCP.
     */
    private function formatToolResponse(array $mcpResult, string $originalMessage): string
    {
        if (!($mcpResult['success'] ?? false)) {
            return "Lo siento, ocurrió un error: " . ($mcpResult['error'] ?? 'Error desconocido');
        }

        $result = $mcpResult['result'] ?? '';

        return $result;
    }

    /**
     * Genera una respuesta conversacional.
     */
    private function generateConversationalResponse(string $userMessage): array
    {
        $prompt = $this->buildConversationalPrompt($userMessage);
        $response = $this->aiService->generate($prompt);

        if (!$response) {
            return $this->errorResponse('No se pudo generar una respuesta');
        }

        return [
            'type' => 'assistant',
            'content' => $response
        ];
    }

    /**
     * Construye el prompt para respuestas conversacionales.
     */
    private function buildConversationalPrompt(string $userMessage): string
    {
        $tools = $this->getAvailableTools();
        $toolsList = array_map(fn($tool) => "- {$tool['name']}: {$tool['description']}", $tools);
        $toolsText = implode("\n", $toolsList);

        return <<<PROMPT
Eres un asistente inteligente que ayuda a los usuarios con operaciones matemáticas y conversación general.

Herramientas disponibles:
{$toolsText}

Si el usuario solicita una operación matemática, puedes procesarla. Para otros temas, responde de forma amigable y útil.

Usuario: {$userMessage}

Asistente:
PROMPT;
    }


    /**
     * Crea una respuesta de error.
     */
    private function errorResponse(string $message): array
    {
        return [
            'type' => 'error',
            'content' => $message
        ];
    }
}
