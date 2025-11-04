<?php

namespace App\Services;

use PhpMcp\Client\Client;
use PhpMcp\Client\Enum\TransportType;
use PhpMcp\Client\Exception\McpClientException;
use PhpMcp\Client\Model\Capabilities as ClientCapabilities;
use PhpMcp\Client\ServerConfig;
use Psr\Log\LoggerInterface;

/**
 * Cliente MCP para conectar con el MCP-RAG Server.
 * Usa php-mcp/client para comunicación MCP sobre HTTP.
 */
class McpRagClientService
{
    private ?Client $client = null;

    private bool $initialized = false;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $ragServerUrl = 'http://localhost:8001/mcp/rag'
    ) {}

    /**
     * Inicializa la conexión con el servidor MCP-RAG.
     */
    public function connect(): void
    {
        if ($this->initialized) {
            return;
        }

        $clientCapabilities = ClientCapabilities::forClient(supportsSampling: false);

        $serverConfig = new ServerConfig(
            name: 'rag_server',
            transport: TransportType::Http,
            timeout: 30.0,
            url: $this->ragServerUrl,
        );

        $this->client = Client::make()
            ->withClientInfo('LaravelRagClient', '1.0.0')
            ->withCapabilities($clientCapabilities)
            ->withServerConfig($serverConfig)
            ->withLogger($this->logger)
            ->build();

        $this->client->initialize();
        $this->initialized = true;

        $this->logger->info('Cliente MCP-RAG conectado exitosamente', [
            'server_name' => $this->client->getServerName(),
            'server_version' => $this->client->getServerVersion(),
            'protocol_version' => $this->client->getNegotiatedProtocolVersion(),
        ]);
    }

    /**
     * Desconecta del servidor MCP-RAG.
     */
    public function disconnect(): void
    {
        if ($this->client && $this->initialized) {
            $this->client->disconnect();
            $this->initialized = false;
            $this->logger->info('Cliente MCP-RAG desconectado');
        }
    }

    /**
     * Sube un documento al servidor RAG.
     */
    public function uploadDocument(string $title, string $content, array $metadata = [], bool $createNewVersion = false): array
    {
        return $this->callTool('upload_document', [
            'document_title' => $title,
            'content' => $content,
            'metadata' => $metadata,
            'create_new_version' => $createNewVersion,
        ]);
    }

    /**
     * Busca documentos semánticamente.
     */
    public function searchSemantic(
        string $query,
        int $limit = 5,
        float $threshold = 0.0,
        ?string $documentId = null,
        bool $groupByDocument = false
    ): array {
        return $this->callTool('search_semantic', [
            'query' => $query,
            'limit' => $limit,
            'threshold' => $threshold,
            'document_id' => $documentId,
            'group_by_document' => $groupByDocument,
        ]);
    }

    /**
     * Obtiene versiones de un documento.
     */
    public function getDocumentVersions(string $documentTitle): array
    {
        return $this->callTool('get_document_versions', [
            'document_title' => $documentTitle,
        ]);
    }

    /**
     * Elimina un documento.
     */
    public function deleteDocument(string $documentId, bool $softDelete = false): array
    {
        return $this->callTool('delete_document', [
            'document_id' => $documentId,
            'soft_delete' => $softDelete,
        ]);
    }

    /**
     * Llama a un tool del servidor MCP-RAG.
     */
    private function callTool(string $toolName, array $arguments): array
    {
        $this->ensureConnected();

        $this->logger->info("Llamando tool MCP-RAG: {$toolName}", $arguments);

        $result = $this->client->callTool($toolName, $arguments);

        $response = [
            'success' => ! $result->isError,
            'tool' => $toolName,
            'arguments' => $arguments,
        ];

        if ($result->isError) {
            $response['error'] = $result->content[0]['text'] ?? 'Error desconocido';
            $this->logger->error("Error en tool {$toolName}", $response);
        } else {
            $response['content'] = $result->content;

            // Extraer fragmentos de búsqueda si es search_semantic
            if ($toolName === 'search_semantic' && ! empty($result->content)) {
                $text = $result->content[0]['text'] ?? '';
                $response['fragments'] = $this->parseSearchResults($text);
            }

            // Extraer texto plano como resultado
            if (! empty($result->content)) {
                $response['result'] = $result->content[0]['text'] ?? null;
            }
        }

        return $response;
    }

    /**
     * Parsea resultados de búsqueda semántica desde el texto de respuesta.
     */
    private function parseSearchResults(string $text): array
    {
        // Esta es una función helper para extraer fragmentos estructurados
        // del texto de respuesta del servidor MCP-RAG
        // Por ahora retornamos array vacío, podrías implementar parsing si es necesario
        return [];
    }

    /**
     * Verifica si el servidor RAG está disponible.
     */
    public function isAvailable(): bool
    {
        if (! $this->initialized) {
            $this->connect();
        }

        if ($this->client && $this->client->isReady()) {
            return true;
        }

        return false;
    }

    /**
     * Verifica el estado de la conexión.
     */
    public function getStatus(): array
    {
        if (! $this->client) {
            return ['status' => 'not_initialized'];
        }

        return [
            'status' => $this->client->getStatus()->value,
            'is_ready' => $this->client->isReady(),
            'server_name' => $this->client->getServerName(),
            'server_version' => $this->client->getServerVersion(),
            'protocol_version' => $this->client->getNegotiatedProtocolVersion(),
        ];
    }

    /**
     * Asegura que el cliente esté conectado.
     */
    private function ensureConnected(): void
    {
        if (! $this->initialized || ! $this->client->isReady()) {
            throw new \RuntimeException(
                'Cliente MCP-RAG no está conectado. Llama a connect() primero.'
            );
        }
    }

    /**
     * Destructor para asegurar la desconexión.
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
