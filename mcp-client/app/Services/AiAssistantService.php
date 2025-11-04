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
        private readonly DocumentService $documentService,
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

        // Verificar disponibilidad de servicios
        if (! $this->aiService->isAvailable()) {
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
            if (! $this->mcpClient->isReady()) {
                $this->mcpClient->connect();
            }

            $toolName = $decision['tool'] ?? null;
            $arguments = $decision['arguments'] ?? [];

            if ($toolName && ! empty($arguments)) {
                $mcpResult = $this->mcpClient->callTool($toolName, $arguments);

                // Desconectar MCP
                $this->mcpClient->disconnect();

                return [
                    'type' => 'assistant',
                    'content' => $this->formatToolResponse($mcpResult, $userMessage),
                    'metadata' => [
                        'tool_used' => $toolName,
                        'arguments' => $arguments,
                        'reasoning' => $decision['reasoning'] ?? '',
                    ],
                ];
            }
        }

        // PASO 3: Si no usa herramientas, generar respuesta conversacional
        return $this->generateConversationalResponse($userMessage);
    }

    /**
     * Procesa un mensaje con respuesta en streaming.
     */
    public function processMessageStreaming(string $userMessage, callable $onChunk): void
    {
        // Verificar disponibilidad
        if (! $this->aiService->isAvailable()) {
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
            if (! $this->mcpClient->isReady()) {
                $this->mcpClient->connect();
            }

            $toolName = $decision['tool'] ?? null;
            $arguments = $decision['arguments'] ?? [];

            if ($toolName && ! empty($arguments)) {
                $mcpResult = $this->mcpClient->callTool($toolName, $arguments);

                // Desconectar MCP
                $this->mcpClient->disconnect();

                $onChunk([
                    'type' => 'assistant',
                    'content' => $this->formatToolResponse($mcpResult, $userMessage),
                    'metadata' => [
                        'tool_used' => $toolName,
                        'arguments' => $arguments,
                        'reasoning' => $decision['reasoning'] ?? '',
                    ],
                ], true);

                return;
            }
        }

        // Respuesta conversacional con streaming
        $fullResponse = '';
        $ragContext = $this->getRelevantContext($userMessage);

        $this->aiService->generateStreaming(
            $this->buildConversationalPrompt($userMessage, $ragContext),
            function (string $chunk, bool $done) use (&$fullResponse, $onChunk, $ragContext) {
                $fullResponse .= $chunk;
                $data = [
                    'type' => 'assistant',
                    'content' => $chunk,
                    'full_content' => $fullResponse,
                    'done' => $done,
                ];

                // Include RAG sources in metadata if available
                if (! empty($ragContext)) {
                    $data['metadata'] = [
                        'rag_sources' => array_map(fn ($ctx) => [
                            'document' => $ctx['document'],
                            'similarity' => $ctx['similarity'],
                        ], $ragContext),
                    ];
                }

                $onChunk($data, $done);
            }
        );
    }

    /**
     * Obtiene las herramientas disponibles.
     */
    public function getAvailableTools(): array
    {
        // Conectar temporalmente si no está conectado
        $wasConnected = $this->mcpClient->isReady();

        if (! $wasConnected) {
            $this->mcpClient->connect();
        }

        $tools = $this->mcpClient->listTools();

        // Desconectar si no estaba conectado previamente
        // Esto evita que la conexión STDIO se quede colgada
        if (! $wasConnected) {
            $this->mcpClient->disconnect();
        }

        return $tools;
    }

    /**
     * Formatea la respuesta de una herramienta MCP.
     */
    private function formatToolResponse(array $mcpResult, string $originalMessage): string
    {
        if (! ($mcpResult['success'] ?? false)) {
            return 'Lo siento, ocurrió un error: '.($mcpResult['error'] ?? 'Error desconocido');
        }

        $result = $mcpResult['result'] ?? '';

        return $result;
    }

    /**
     * Genera una respuesta conversacional.
     */
    private function generateConversationalResponse(string $userMessage): array
    {
        $ragContext = $this->getRelevantContext($userMessage);
        $prompt = $this->buildConversationalPrompt($userMessage, $ragContext);
        $response = $this->aiService->generate($prompt);

        if (! $response) {
            return $this->errorResponse('No se pudo generar una respuesta');
        }

        $result = [
            'type' => 'assistant',
            'content' => $response,
        ];

        // Include RAG sources in metadata if available
        if (! empty($ragContext)) {
            $result['metadata'] = [
                'rag_sources' => array_map(fn ($ctx) => [
                    'document' => $ctx['document'],
                    'similarity' => $ctx['similarity'],
                ], $ragContext),
            ];
        }

        return $result;
    }

    /**
     * Construye el prompt para respuestas conversacionales con contexto RAG.
     */
    private function buildConversationalPrompt(string $userMessage, array $ragContext = []): string
    {

        $tools = $this->getAvailableTools();
        $toolsList = array_map(fn ($tool) => "- {$tool['name']}: {$tool['description']}", $tools);
        $toolsText = implode("\n", $toolsList);

        $contextSection = '';
        if (! empty($ragContext)) {
            $contextSection = "\n## Contexto Relevante de Documentos:\n\n";
            foreach ($ragContext as $i => $ctx) {
                $contextSection .= sprintf(
                    "[%d] Documento: %s (Similitud: %.0f%%)\n%s\n\n",
                    $i + 1,
                    $ctx['document'],
                    $ctx['similarity'] * 100,
                    $ctx['content']
                );
            }
        }

        return <<<PROMPT
Eres un asistente inteligente basado en RAG (Retrieval-Augmented Generation).

Tu rol es ayudar a los usuarios respondiendo preguntas basándote en el contexto de documentos
proporcionados, así como proporcionar información general y realizar operaciones matemáticas.

Herramientas MCP disponibles:
{$toolsText}
{$contextSection}
## Pregunta del Usuario:
{$userMessage}

## Instrucciones:
- Si hay contexto relevante de documentos, úsalo prioritariamente para responder
- Cita siempre los documentos de los que obtienes información usando [número]
- Si no hay contexto relevante o la pregunta es general, responde con tu conocimiento
- Si necesitas hacer cálculos matemáticos, usa las herramientas disponibles
- Sé preciso, claro y conciso

Asistente:
PROMPT;
    }

    /**
     * Obtiene contexto relevante del RAG para una consulta.
     */
    private function getRelevantContext(string $query): array
    {
        // Verificar si RAG está disponible
        if (! $this->documentService->checkConnection()) {
            $this->logger->warning('RAG server no disponible, continuando sin contexto');

            return [];
        }

        // Buscar contexto
        $result = $this->documentService->searchContext($query, 3);

        if ($result['success'] && ! empty($result['contexts'])) {
            $this->logger->info('Contexto RAG obtenido', [
                'query' => $query,
                'contexts_count' => $result['count'],
            ]);

            return $result['contexts'];
        }

        return [];
    }

    /**
     * Crea una respuesta de error.
     */
    private function errorResponse(string $message): array
    {
        return [
            'type' => 'error',
            'content' => $message,
        ];
    }
}
