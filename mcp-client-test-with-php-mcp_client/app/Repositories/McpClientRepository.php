<?php

namespace App\Repositories;

use App\Contracts\McpClientInterface;
use App\Services\McpCalculatorClient;

/**
 * Repository para manejar operaciones con el cliente MCP.
 *
 * Implementa el patrón Repository para abstraer la lógica de acceso a MCP.
 * Sigue el principio de Single Responsibility (SOLID).
 */
class McpClientRepository implements McpClientInterface
{
    public function __construct(
        private readonly McpCalculatorClient $mcpClient
    ) {}

    public function connect(): void
    {
        $this->mcpClient->connect();
    }

    public function disconnect(): void
    {
        $this->mcpClient->disconnect();
    }

    public function listTools(): array
    {
        return $this->mcpClient->listTools();
    }

    public function callTool(string $toolName, array $arguments): array
    {
        return $this->mcpClient->callTool($toolName, $arguments);
    }

    public function getStatus(): array
    {
        return $this->mcpClient->getStatus();
    }

    public function isReady(): bool
    {
        $status = $this->mcpClient->getStatus();
        return $status['is_ready'] ?? false;
    }
}
