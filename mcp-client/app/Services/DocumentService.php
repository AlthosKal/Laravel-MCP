<?php

namespace App\Services;

use Psr\Log\LoggerInterface;

/**
 * Servicio de alto nivel para gestión de documentos.
 * Orquesta las operaciones del RAG server.
 */
class DocumentService
{
    public function __construct(
        private readonly McpRagClientService $ragClient,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Sube un nuevo documento.
     */
    public function uploadDocument(string $title, string $content, array $metadata = []): array
    {
        $result = $this->ragClient->uploadDocument($title, $content, $metadata, false);

        $this->logger->info('Documento subido exitosamente', [
            'title' => $title,
            'result' => $result,
        ]);

        return [
            'success' => true,
            'message' => 'Documento procesado exitosamente',
            'data' => $result,
        ];
    }

    /**
     * Actualiza un documento (crea nueva versión).
     */
    public function updateDocument(string $title, string $content, array $metadata = []): array
    {
        $result = $this->ragClient->uploadDocument($title, $content, $metadata, true);

        return [
            'success' => true,
            'message' => 'Nueva versión creada exitosamente',
            'data' => $result,
        ];
    }

    /**
     * Lista versiones de un documento.
     */
    public function listVersions(string $title): array
    {
        $result = $this->ragClient->getDocumentVersions($title);

        return [
            'success' => true,
            'data' => $result,
        ];
    }

    /**
     * Elimina un documento.
     */
    public function deleteDocument(string $documentId, bool $softDelete = true): array
    {
        $result = $this->ragClient->deleteDocument($documentId, $softDelete);

        return [
            'success' => true,
            'message' => 'Documento eliminado exitosamente',
            'data' => $result,
        ];
    }

    /**
     * Busca contexto relevante para una consulta.
     */
    public function searchContext(string $query, int $limit = 5): array
    {
        $result = $this->ragClient->searchSemantic($query, $limit, 0.0, null, false);

        // Extraer solo el contenido relevante
        if (isset($result['fragments'])) {
            $contexts = array_map(function ($fragment) {
                return [
                    'content' => $fragment['content'],
                    'document' => $fragment['document_title'],
                    'similarity' => $fragment['similarity'],
                    'chunk_index' => $fragment['chunk_index'],
                ];
            }, $result['fragments']);

            return [
                'success' => true,
                'contexts' => $contexts,
                'count' => count($contexts),
            ];
        }

        return [
            'success' => true,
            'contexts' => [],
            'count' => 0,
        ];
    }

    /**
     * Verifica conectividad con RAG server.
     */
    public function checkConnection(): bool
    {
        return $this->ragClient->isAvailable();
    }
}
