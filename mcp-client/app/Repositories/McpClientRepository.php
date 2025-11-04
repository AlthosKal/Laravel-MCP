<?php

namespace App\Repositories;

use App\Contracts\McpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Repository para manejar operaciones con el cliente MCP.
 *
 * Implementa el patrón Repository para abstraer la lógica de acceso a MCP.
 * Sigue el principio de Single Responsibility (SOLID).
 *
 * Nota: Este repository es un stub. No hay cliente MCP genérico configurado.
 * Para operaciones RAG, usa McpRagClientService directamente.
 */
class McpClientRepository implements McpClientInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function connect(): void
    {
        $this->logger->warning('McpClientRepository: No MCP client configured');
    }

    public function disconnect(): void
    {
        // No-op
    }

    public function listTools(): array
    {
        $this->logger->warning('McpClientRepository: No MCP client configured, returning empty tools');

        return [];
    }

    public function callTool(string $toolName, array $arguments): array
    {
        $this->logger->error('McpClientRepository: Cannot call tool - no MCP client configured', [
            'tool' => $toolName,
            'arguments' => $arguments,
        ]);

        return [
            'success' => false,
            'error' => 'No MCP client configured',
        ];
    }

    public function getStatus(): array
    {
        return [
            'status' => 'not_configured',
            'is_ready' => false,
        ];
    }

    public function isReady(): bool
    {
        return false;
    }
}
