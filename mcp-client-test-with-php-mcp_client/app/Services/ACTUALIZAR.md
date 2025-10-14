# Métodos a eliminar del archivo AiAssistantService.php

Después de confirmar que todo funciona, eliminar este método que ya no se usa:

```php
private function executeMathOperation(array $mathOperation, string $originalMessage): array
```

Este método fue reemplazado por la lógica directa en `processMessage` y `processMessageStreaming`.

También agregar este nuevo método:

```php
private function formatToolResponse(array $mcpResult, string $originalMessage): string
{
    if (!($mcpResult['success'] ?? false)) {
        return "Lo siento, ocurrió un error: " . ($mcpResult['error'] ?? 'Error desconocido');
    }

    $result = $mcpResult['result'] ?? '';
    $tool = $mcpResult['tool'] ?? 'operación';

    return "{$result}";
}
```
