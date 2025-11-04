<?php

namespace App\Exceptions;

/**
 * Excepción cuando falla la generación de embeddings.
 */
class EmbeddingException extends RagException
{
    public function __construct(
        string $message = 'Error al generar embeddings',
        int $code = 500,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
