<?php

use App\Mcp\Servers\ProcessesServer;
use Laravel\Mcp\Facades\Mcp;

// Servidor HTTP para acceso remoto
Mcp::web('/mcp/processes', ProcessesServer::class);

// Servidor local para uso CLI
Mcp::local('processes', ProcessesServer::class);
