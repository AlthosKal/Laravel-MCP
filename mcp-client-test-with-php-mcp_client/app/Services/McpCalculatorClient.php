<?php

namespace App\Services;

use PhpMcp\Client\Client;
use PhpMcp\Client\Enum\TransportType;
use PhpMcp\Client\Exception\McpClientException;
use PhpMcp\Client\Model\Capabilities as ClientCapabilities;
use PhpMcp\Client\ServerConfig;
use Psr\Log\LoggerInterface;

/**
 * Cliente MCP para interactuar con el servidor de calculadora Laravel/MCP.
 */

class McpCalculatorClient
{
    private ?Client $client = null;

    private bool $initialized = false;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $serverType = 'http',
        private readonly ?string $serverUrl = null,
        private readonly ?string $serverCommand = null,
        private readonly ?array $serverArgs = null,
    ){}

    /**
     * Inicializa la conexión con el servidor MCP.
     */

    public function connect(): void
    {
        if ($this->initialized) {
            return;
        }

        try {
            $clientCapabilities = ClientCapabilities::forClient(supportsSampling: false);
            
            $serverConfig = $this->serverType === 'http'
                ? $this->createHttpConfig()
                : $this->createStdioConfig();

            $this->client = Client::make()
                ->withClientInfo('LaravelMcpClient', '1.0.0')
                ->withCapabilities($clientCapabilities)
                ->withServerConfig($serverConfig)
                ->withLogger($this->logger)
                ->build();

            $this->client->initialize();
            $this->initialized = true;

            $this->logger->info('Cliente MCP conectado exitosamente', [
                'server_name' => $this->client->getServerName(),
                'server_version' => $this->client->getServerVersion(),
                'protocol_version' => $this->client->getNegotiatedProtocolVersion(),
            ]);
        } catch (McpClientException $e) {
            $this->logger->error('Error al conectar con el servidor MCP', [
                'error' => $e->getMessage(),
                'type' => get_class($e),
            ]);
            throw $e;
        }
    }

    /**
     * Crea configuración para servidor HTTP.
     */
    private function createHttpConfig(): ServerConfig
    {
        $url = $this->serverUrl ?? 'http://localhost:8000/mcp/calculator';

        return new ServerConfig(
            name: 'calculator_http',
            transport: TransportType::Http,
            timeout: 30.0,
            url: $url,
        );
    }

    /**
     * Crea configuración para servidor STDIO.
     */
    private function createStdioConfig(): ServerConfig
    {
        $command = $this->serverCommand ?? 'php';
        $args = $this->serverArgs ?? [
            base_path('../mcp-server-test-with-laravel_mcp/artisan'),
            'mcp:start',
            'calculator',
        ];

        return new ServerConfig(
            name: 'calculator_stdio',
            transport: TransportType::Stdio,
            timeout: 30.0,
            command: $command,
            args: $args,
        );
    }

    /**
     * Desconecta del servidor MCP.
     */
    public function disconnect(): void
    {
        if ($this->client && $this->initialized) {
            try {
                $this->client->disconnect();
                $this->initialized = false;
                $this->logger->info('Cliente MCP desconectado');
            } catch (\Throwable $e) {
                $this->logger->error('Error al desconectar', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Lista todas las herramientas disponibles.
     */
    public function listTools(): array
    {
        $this->ensureConnected();

        try {
            $tools = $this->client->listTools();
            
            return array_map(function ($tool) {
                return [
                    'name' => $tool->name,
                    'description' => $tool->description ?? 'Sin descripción',
                    'input_schema' => $tool->inputSchema ?? [],
                ];
            }, $tools);
        } catch (McpClientException $e) {
            $this->logger->error('Error al listar herramientas', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Suma dos números.
     */
    public function add(float $a, float $b): array
    {
        return $this->callTool('add', compact('a', 'b'));
    }

    /**
     * Resta dos números.
     */
    public function subtract(float $a, float $b): array
    {
        return $this->callTool('subtract', compact('a', 'b'));
    }

    /**
     * Multiplica dos números.
     */
    public function multiply(float $a, float $b): array
    {
        return $this->callTool('multiply', compact('a', 'b'));
    }

    /**
     * Divide dos números.
     */
    public function divide(float $a, float $b): array
    {
        return $this->callTool('divide', compact('a', 'b'));
    }

    /**
     * Llama a una herramienta del servidor MCP.
     */
    public function callTool(string $toolName, array $arguments): array
    {
        $this->ensureConnected();

        try {
            $this->logger->info("Llamando herramienta: {$toolName}", $arguments);
            
            $result = $this->client->callTool($toolName, $arguments);
            
            $response = [
                'success' => !$result->isError,
                'tool' => $toolName,
                'arguments' => $arguments,
            ];

            if ($result->isError) {
                $response['error'] = $result->content[0]['text'] ?? 'Error desconocido';
            } else {
                $response['content'] = $result->content;
                
                // Extraer el texto de la respuesta
                if (!empty($result->content)) {
                    $response['result'] = $result->content[0]['text'] ?? null;
                }
            }

            $this->logger->info("Resultado de {$toolName}", $response);

            return $response;
        } catch (McpClientException $e) {
            $this->logger->error("Error al llamar herramienta {$toolName}", [
                'error' => $e->getMessage(),
                'type' => get_class($e),
            ]);

            return [
                'success' => false,
                'tool' => $toolName,
                'arguments' => $arguments,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
            ];
        }
    }

    /**
     * Verifica el estado de la conexión.
     */
    public function getStatus(): array
    {
        if (!$this->client) {
            return ['status' => 'not_initialized'];
        }

        return [
            'status' => $this->client->getStatus()->value,
            'is_ready' => $this->client->isReady(),
            'server_name' => $this->client->getServerName(),
            'server_version' => $this->client->getServerVersion(),
            'protocol_version' => $this->client->getNegotiatedProtocolVersion(),
        ];
    }

    /**
     * Asegura que el cliente esté conectado.
     */
    private function ensureConnected(): void
    {
        if (!$this->initialized || !$this->client->isReady()) {
            throw new \RuntimeException(
                'Cliente MCP no está conectado. Llama a connect() primero.'
            );
        }
    }

    /**
     * Destructor para asegurar la desconexión.
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
