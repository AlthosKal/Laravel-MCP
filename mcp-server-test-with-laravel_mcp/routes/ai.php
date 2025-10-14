<?php

use App\Mcp\Servers\Calculator;
use Laravel\Mcp\Facades\Mcp;

// Servidor HTTP para acceso remoto
Mcp::web('/mcp/calculator', Calculator::class);

// Servidor local para uso CLI
Mcp::local('calculator', Calculator::class);
