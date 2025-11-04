<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\ExtractEntitiesTool;
use App\Mcp\Tools\FormatStructuredTool;
use App\Mcp\Tools\GenerateTemplateReportTool;
use App\Mcp\Tools\ValidateContentTool;
use Laravel\Mcp\Server;

class ProcessesServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'ProcessesServer';

    /**
     * The MCP server's version.
     */
    protected string $version = '1.0.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        Este servidor proporciona herramientas de procesamiento de texto y formateo.

        Capacidades disponibles:
        - Formatear texto en estructuras JSON, XML, YAML, CSV
        - Extraer entidades (emails, URLs, teléfonos, fechas, números, hashtags)
        - Validar y limpiar contenido de texto
        - Generar reportes usando plantillas predefinidas (summary, detailed, markdown, html)

        Todas las herramientas son útiles para procesamiento posterior de respuestas RAG.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        FormatStructuredTool::class,
        ExtractEntitiesTool::class,
        ValidateContentTool::class,
        GenerateTemplateReportTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [];
}
