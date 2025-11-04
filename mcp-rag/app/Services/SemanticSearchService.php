<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

/**
 * Servicio para búsqueda semántica usando pgvector.
 * Implementa búsqueda por similitud coseno con caché.
 */
class SemanticSearchService
{
    private const CACHE_TTL = 3600; // 1 hora

    private const DEFAULT_LIMIT = 5;

    public function __construct(
        private readonly EmbeddingService $embeddingService,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Busca fragmentos similares a una consulta usando embeddings.
     *
     * @param  string  $query  La consulta de búsqueda
     * @param  int  $limit  Número máximo de resultados
     * @param  float  $threshold  Umbral mínimo de similitud (0-1)
     * @param  string|null  $documentId  Filtrar por documento específico
     * @return array Array de resultados con fragmentos y scores
     */
    public function search(
        string $query,
        int $limit = self::DEFAULT_LIMIT,
        float $threshold = 0.0,
        ?string $documentId = null
    ): array {
        // Generar embedding de la consulta
        $queryEmbedding = $this->embeddingService->generateEmbedding($query);
        $vectorString = $this->embeddingService->embeddingToVector($queryEmbedding);

        // Construir query SQL con pgvector
        $sql = '
            SELECT
                fd.id,
                fd.id_metadata_document,
                fd.chunk_index,
                fd.content,
                md.document_title,
                md.version,
                md.metadata,
                1 - (fd.embedding <=> :vector::vector) as similarity
            FROM fragment_documents fd
            INNER JOIN metadata_documents md ON fd.id_metadata_document = md.id
            WHERE md.valid = true
        ';

        $bindings = ['vector' => $vectorString];

        // Filtrar por documento si se especifica
        if ($documentId !== null) {
            $sql .= ' AND fd.id_metadata_document = :document_id';
            $bindings['document_id'] = $documentId;
        }

        // Filtrar por umbral de similitud
        if ($threshold > 0) {
            $sql .= ' AND (1 - (fd.embedding <=> :vector::vector)) >= :threshold';
            $bindings['threshold'] = $threshold;
        }

        $sql .= '
            ORDER BY fd.embedding <=> :vector::vector
            LIMIT :limit
        ';
        $bindings['limit'] = $limit;

        $results = DB::select($sql, $bindings);

        $this->logger->info('Búsqueda semántica ejecutada', [
            'query_length' => strlen($query),
            'results_count' => count($results),
            'limit' => $limit,
            'threshold' => $threshold,
        ]);

        return array_map(function ($result) {
            return [
                'fragment_id' => $result->id,
                'document_id' => $result->id_metadata_document,
                'document_title' => $result->document_title,
                'document_version' => $result->version,
                'chunk_index' => $result->chunk_index,
                'content' => $result->content,
                'metadata' => json_decode($result->metadata, true),
                'similarity' => round($result->similarity, 4),
            ];
        }, $results);
    }

    /**
     * Busca fragmentos similares con caché.
     *
     * @param  string  $query  La consulta de búsqueda
     * @param  int  $limit  Número máximo de resultados
     * @return array Array de resultados
     */
    public function searchCached(string $query, int $limit = self::DEFAULT_LIMIT): array
    {
        $cacheKey = $this->getCacheKey($query, $limit);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($query, $limit) {
            return $this->search($query, $limit);
        });
    }

    /**
     * Busca y agrupa fragmentos por documento.
     *
     * @param  string  $query  La consulta de búsqueda
     * @param  int  $fragmentsPerDocument  Fragmentos por documento
     * @param  int  $maxDocuments  Máximo de documentos a retornar
     * @return array Resultados agrupados por documento
     */
    public function searchGroupedByDocument(
        string $query,
        int $fragmentsPerDocument = 3,
        int $maxDocuments = 3
    ): array {
        // Buscar más fragmentos de los necesarios para poder agrupar
        $results = $this->search($query, $fragmentsPerDocument * $maxDocuments * 2);

        // Agrupar por documento
        $grouped = [];
        foreach ($results as $result) {
            $docId = $result['document_id'];

            if (! isset($grouped[$docId])) {
                $grouped[$docId] = [
                    'document_id' => $docId,
                    'document_title' => $result['document_title'],
                    'document_version' => $result['document_version'],
                    'metadata' => $result['metadata'],
                    'fragments' => [],
                    'avg_similarity' => 0,
                ];
            }

            if (count($grouped[$docId]['fragments']) < $fragmentsPerDocument) {
                $grouped[$docId]['fragments'][] = $result;
            }
        }

        // Calcular similitud promedio por documento y limitar
        $grouped = array_slice($grouped, 0, $maxDocuments);

        foreach ($grouped as &$doc) {
            $similarities = array_column($doc['fragments'], 'similarity');
            $doc['avg_similarity'] = count($similarities) > 0
                ? round(array_sum($similarities) / count($similarities), 4)
                : 0;
        }

        // Ordenar por similitud promedio
        usort($grouped, fn ($a, $b) => $b['avg_similarity'] <=> $a['avg_similarity']);

        return array_values($grouped);
    }

    /**
     * Genera clave de caché para una búsqueda.
     */
    private function getCacheKey(string $query, int $limit): string
    {
        return 'semantic_search:'.md5($query.':'.$limit);
    }

    /**
     * Invalida la caché de búsquedas.
     */
    public function invalidateCache(): void
    {
        // En producción, considera usar tags de caché de Redis
        Cache::flush();
        $this->logger->info('Caché de búsquedas invalidada');
    }
}
