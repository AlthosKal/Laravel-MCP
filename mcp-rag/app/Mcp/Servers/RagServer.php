<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\DeleteDocumentTool;
use App\Mcp\Tools\GetDocumentVersionsTool;
use App\Mcp\Tools\SearchSemanticTool;
use App\Mcp\Tools\UploadDocumentTool;
use Laravel\Mcp\Server;

/**
 * Servidor MCP para funcionalidades RAG (Retrieval-Augmented Generation).
 */
class RagServer extends Server
{
    public static function name(): string
    {
        return 'rag-server';
    }

    public static function version(): string
    {
        return '1.0.0';
    }

    public function tools(): array
    {
        return [
            UploadDocumentTool::class,
            SearchSemanticTool::class,
            GetDocumentVersionsTool::class,
            DeleteDocumentTool::class,
        ];
    }

    public function prompts(): array
    {
        return [];
    }

    public function resources(): array
    {
        return [];
    }
}
