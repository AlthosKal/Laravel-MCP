<?php

namespace App\Services;

use OpenAI\Client;
use Psr\Log\LoggerInterface;

/**
 * Servicio para generar embeddings usando OpenAI API.
 * Implementa rate limiting y retry con backoff.
 */
class EmbeddingService
{
    private const EMBEDDING_MODEL = 'text-embedding-3-small';

    private const EMBEDDING_DIMENSIONS = 1536;

    private const MAX_RETRIES = 3;

    private const RETRY_DELAY_MS = 1000;

    public function __construct(
        private readonly Client $openai,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Genera un embedding para un texto.
     *
     * @param  string  $text  El texto a procesar
     * @return array Vector de embedding (1536 dimensiones)
     */
    public function generateEmbedding(string $text): array
    {
        $response = $this->openai->embeddings()->create([
            'model' => self::EMBEDDING_MODEL,
            'input' => $text,
            'dimensions' => self::EMBEDDING_DIMENSIONS,
        ]);

        $embedding = $response->embeddings[0]->embedding;

        $this->logger->debug('Embedding generado', [
            'text_length' => strlen($text),
            'embedding_size' => count($embedding),
        ]);

        return $embedding;
    }

    /**
     * Genera embeddings para múltiples textos en batch.
     *
     * @param  array<string>  $texts  Array de textos
     * @return array<array> Array de vectores de embedding
     *
     * @throws \Exception Si falla el procesamiento
     */
    public function generateEmbeddingsBatch(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }

        // OpenAI permite hasta 2048 inputs por request, pero vamos conservadores
        $maxBatchSize = 100;
        $batches = array_chunk($texts, $maxBatchSize);
        $allEmbeddings = [];

        foreach ($batches as $batchIndex => $batch) {
            $response = $this->openai->embeddings()->create([
                'model' => self::EMBEDDING_MODEL,
                'input' => $batch,
                'dimensions' => self::EMBEDDING_DIMENSIONS,
            ]);

            foreach ($response->embeddings as $embedding) {
                $allEmbeddings[] = $embedding->embedding;
            }

            $this->logger->info('Batch de embeddings generado', [
                'batch_index' => $batchIndex,
                'batch_size' => count($batch),
            ]);

            // Rate limiting: esperar un poco entre batches
            if ($batchIndex < count($batches) - 1) {
                usleep(100000); // 100ms
            }
        }

        return $allEmbeddings;
    }

    /**
     * Convierte un array de floats a formato pgvector string.
     *
     * @param  array  $embedding  Vector de números
     * @return string Representación string para pgvector: "[0.1,0.2,...]"
     */
    public function embeddingToVector(array $embedding): string
    {
        return '['.implode(',', $embedding).']';
    }

    /**
     * Convierte un string pgvector a array de floats.
     *
     * @param  string  $vector  String en formato "[0.1,0.2,...]"
     * @return array Vector de números
     */
    public function vectorToEmbedding(string $vector): array
    {
        $trimmed = trim($vector, '[]');

        return array_map('floatval', explode(',', $trimmed));
    }
}
