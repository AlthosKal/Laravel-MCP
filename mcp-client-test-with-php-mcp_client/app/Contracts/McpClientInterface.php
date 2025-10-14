<?php

namespace App\Contracts;

/**
 * Interface McpClientInterface
 *
 * Define el contrato para interactuar con servidores MCP.
 * Siguiendo el principio de Inversión de Dependencias (SOLID).
 */
interface McpClientInterface
{
    /**
     * Conecta al servidor MCP.
     */
    public function connect(): void;

    /**
     * Desconecta del servidor MCP.
     */
    public function disconnect(): void;

    /**
     * Lista las herramientas disponibles en el servidor MCP.
     */
    public function listTools(): array;

    /**
     * Ejecuta una herramienta del servidor MCP.
     */
    public function callTool(string $toolName, array $arguments): array;

    /**
     * Obtiene el estado de la conexión.
     */
    public function getStatus(): array;

    /**
     * Verifica si está conectado y listo.
     */
    public function isReady(): bool;
}
