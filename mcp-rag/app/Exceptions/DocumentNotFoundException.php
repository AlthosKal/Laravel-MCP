<?php

namespace App\Exceptions;

/**
 * Excepción cuando no se encuentra un documento.
 */
class DocumentNotFoundException extends RagException
{
    public function __construct(
        string $message = 'Documento no encontrado',
        int $code = 404,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
