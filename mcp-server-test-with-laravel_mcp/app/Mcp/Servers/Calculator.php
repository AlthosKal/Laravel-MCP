<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddTool;
use App\Mcp\Tools\DivideTool;
use App\Mcp\Tools\MultiplyTool;
use App\Mcp\Tools\SubstractTool;
use Laravel\Mcp\Server;

class Calculator extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Calculator';

    /**
     * The MCP server's version.
     */
    protected string $version = '1.0.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        Este servidor proporciona operaciones matemáticas básicas.
        
        Capacidades disponibles:
        - Suma de dos números
        - Resta de dos números
        - Multiplicación de dos números
        - División de dos números (con validación de división por cero)
        
        Todos los cálculos aceptan números decimales y retornan resultados precisos.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        AddTool::class,
        SubstractTool::class,
        MultiplyTool::class,
        DivideTool::class,
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
