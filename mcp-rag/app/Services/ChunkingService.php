<?php

namespace App\Services;

/**
 * Servicio para dividir texto en fragmentos (chunks).
 * Implementa estrategias de chunking para optimizar la búsqueda semántica.
 */
class ChunkingService
{
    private int $maxTokens;

    private int $overlapTokens;

    /**
     * @param  int  $maxTokens  Número máximo de tokens por chunk (default: 800)
     * @param  int  $overlapTokens  Número de tokens de solapamiento entre chunks (default: 100)
     */
    public function __construct(
        int $maxTokens = 800,
        int $overlapTokens = 100
    ) {
        $this->maxTokens = $maxTokens;
        $this->overlapTokens = $overlapTokens;
    }

    /**
     * Divide un texto en fragmentos de tamaño óptimo.
     *
     * @param  string  $content  El texto a dividir
     * @return array<int, string> Array de fragmentos
     */
    public function chunkText(string $content): array
    {
        // Normalizar saltos de línea
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // Dividir por párrafos primero
        $paragraphs = array_filter(explode("\n\n", $content), fn ($p) => trim($p) !== '');

        $chunks = [];
        $currentChunk = '';
        $currentTokens = 0;

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            $paragraphTokens = $this->estimateTokens($paragraph);

            // Si un solo párrafo es demasiado grande, dividirlo por oraciones
            if ($paragraphTokens > $this->maxTokens) {
                $sentences = $this->splitIntoSentences($paragraph);

                foreach ($sentences as $sentence) {
                    $sentenceTokens = $this->estimateTokens($sentence);

                    if ($currentTokens + $sentenceTokens > $this->maxTokens) {
                        if ($currentChunk !== '') {
                            $chunks[] = trim($currentChunk);

                            // Mantener overlap
                            $overlap = $this->getOverlap($currentChunk);
                            $currentChunk = $overlap.' '.$sentence;
                            $currentTokens = $this->estimateTokens($currentChunk);
                        } else {
                            $currentChunk = $sentence;
                            $currentTokens = $sentenceTokens;
                        }
                    } else {
                        $currentChunk .= ' '.$sentence;
                        $currentTokens += $sentenceTokens;
                    }
                }
            } else {
                // El párrafo cabe completo
                if ($currentTokens + $paragraphTokens > $this->maxTokens) {
                    if ($currentChunk !== '') {
                        $chunks[] = trim($currentChunk);

                        // Mantener overlap
                        $overlap = $this->getOverlap($currentChunk);
                        $currentChunk = $overlap."\n\n".$paragraph;
                        $currentTokens = $this->estimateTokens($currentChunk);
                    } else {
                        $currentChunk = $paragraph;
                        $currentTokens = $paragraphTokens;
                    }
                } else {
                    $currentChunk .= "\n\n".$paragraph;
                    $currentTokens += $paragraphTokens;
                }
            }
        }

        // Agregar el último chunk
        if ($currentChunk !== '') {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }

    /**
     * Estima el número de tokens en un texto.
     * Aproximación: ~4 caracteres por token en español/inglés.
     */
    private function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Divide un texto en oraciones.
     */
    private function splitIntoSentences(string $text): array
    {
        // Regex para dividir por puntos, signos de exclamación e interrogación
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        return array_filter($sentences, fn ($s) => trim($s) !== '');
    }

    /**
     * Obtiene el overlap (últimas oraciones) de un chunk.
     */
    private function getOverlap(string $text): string
    {
        $sentences = $this->splitIntoSentences($text);
        $overlap = '';
        $tokens = 0;

        // Tomar oraciones desde el final hasta alcanzar overlapTokens
        for ($i = count($sentences) - 1; $i >= 0; $i--) {
            $sentenceTokens = $this->estimateTokens($sentences[$i]);

            if ($tokens + $sentenceTokens > $this->overlapTokens) {
                break;
            }

            $overlap = $sentences[$i].' '.$overlap;
            $tokens += $sentenceTokens;
        }

        return trim($overlap);
    }
}
