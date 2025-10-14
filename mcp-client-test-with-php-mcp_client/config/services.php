<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ollama Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Ollama AI service.
    |
    */

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'model' => env('OLLAMA_MODEL', 'mistral'),
        'timeout' => env('OLLAMA_TIMEOUT', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP (Model Context Protocol) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for MCP servers.
    |
    */

    'mcp' => [
        'calculator' => [
            'type' => env('MCP_CALCULATOR_TYPE', 'stdio'), // 'stdio' or 'http'
            'http_url' => env('MCP_CALCULATOR_HTTP_URL', 'http://localhost:8000/mcp/calculator'),
            'command' => env('MCP_CALCULATOR_COMMAND', 'php'),
            'args' => [
                base_path('../mcp-server-test-with-laravel_mcp/artisan'),
                'mcp:start',
                'calculator',
            ],
            'timeout' => env('MCP_CALCULATOR_TIMEOUT', 30),
        ],
    ],

];
