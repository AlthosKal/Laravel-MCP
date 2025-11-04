<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| MCP-Processes Server - API Only
| No web routes needed. Use:
| - /mcp/processes for MCP server
| - /up for health check
|
*/

Route::get('/', function () {
    return response()->json([
        'service' => 'MCP-Processes Server',
        'version' => '1.0.0',
        'status' => 'running',
        'endpoints' => [
            'mcp' => '/mcp/processes',
            'health' => '/up',
        ],
        'tools' => [
            'format_structured',
            'extract_entities',
            'validate_content',
            'generate_template_report',
        ],
    ]);
});
