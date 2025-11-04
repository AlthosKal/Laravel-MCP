<?php

use App\Mcp\Servers\RagServer;
use Laravel\Mcp\Facades\Mcp;

// Servidor HTTP para acceso remoto desde otros servicios
Mcp::web('/mcp/rag', RagServer::class);

// Servidor local para uso CLI (desarrollo y testing)
Mcp::local('rag', RagServer::class);
