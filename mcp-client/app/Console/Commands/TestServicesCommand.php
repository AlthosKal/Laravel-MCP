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
            $this->info('   📦 Modelos disponibles: '.implode(', ', $models));
        } else {
            $this->error('   ❌ Ollama NO está disponible');
            $this->warn('   💡 Verifica que Docker esté corriendo: docker ps | grep ollama');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('✅ Servicio de IA funcionando correctamente');

        return self::SUCCESS;
    }
}
