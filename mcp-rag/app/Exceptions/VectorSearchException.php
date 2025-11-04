<?php

namespace App\Exceptions;

/**
 * Excepción cuando falla la búsqueda vectorial.
 */
class VectorSearchException extends RagException
{
    public function __construct(
        string $message = 'Error en búsqueda vectorial',
        int $code = 500,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
