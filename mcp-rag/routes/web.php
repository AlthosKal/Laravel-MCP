<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| MCP-RAG Server - API Only
| No web routes needed. Use:
| - /mcp/rag for MCP server
| - /up for health check
|
*/

Route::get('/', function () {
    return response()->json([
        'service' => 'MCP-RAG Server',
        'version' => '1.0.0',
        'status' => 'running',
        'endpoints' => [
            'mcp' => '/mcp/rag',
            'health' => '/up',
        ],
    ]);
});
