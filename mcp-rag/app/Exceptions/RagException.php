<?php

namespace App\Exceptions;

use Exception;

/**
 * Excepción base para el sistema RAG.
 */
class RagException extends Exception
{
    public function __construct(
        string $message = 'Error en el sistema RAG',
        int $code = 0,
        ?\Throwable $previous = null,
        protected array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function report(): void
    {
        logger()->error($this->getMessage(), [
            'exception' => static::class,
            'code' => $this->getCode(),
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ]);
    }
}
