<?php

namespace App\Console\Commands;

use App\Contracts\AiServiceInterface;
use App\Contracts\McpClientInterface;
use Illuminate\Console\Command;

class TestServicesCommand extends Command
{
    protected $signature = 'test:services';
    protected $description = 'Prueba la conectividad con Ollama y MCP Server';

    public function __construct(
        private readonly AiServiceInterface $aiService,
        private readonly McpClientInterface $mcpClient
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('🔍 Probando servicios...');
        $this->newLine();

        // Test Ollama
        $this->info('1️⃣  Probando Ollama...');
        if ($this->aiService->isAvailable()) {
            $this->info('   ✅ Ollama está disponible');

            $models = $this->aiService->listModels();
            $this->info('   📦 Modelos disponibles: ' . implode(', ', $models));
        } else {
            $this->error('   ❌ Ollama NO está disponible');
            $this->warn('   💡 Verifica que Docker esté corriendo: docker ps | grep ollama');
            return self::FAILURE;
        }

        $this->newLine();

        // Test MCP
        $this->info('2️⃣  Probando MCP Server...');
        try {
            $this->mcpClient->connect();
            $this->info('   ✅ Conexión MCP establecida');

            $status = $this->mcpClient->getStatus();
            $this->info('   📊 Server: ' . ($status['server_name'] ?? 'N/A'));
            $this->info('   🔢 Versión: ' . ($status['server_version'] ?? 'N/A'));

            $tools = $this->mcpClient->listTools();
            $this->info('   🛠️  Herramientas disponibles: ' . count($tools));

            foreach ($tools as $tool) {
                $this->info('      - ' . $tool['name'] . ': ' . $tool['description']);
            }

            $this->mcpClient->disconnect();
            $this->info('   ✅ Desconexión exitosa');
        } catch (\Throwable $e) {
            $this->error('   ❌ Error en MCP: ' . $e->getMessage());
            $this->warn('   💡 Verifica que el servidor MCP esté corriendo');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('✅ Todos los servicios están funcionando correctamente');

        return self::SUCCESS;
    }
}
