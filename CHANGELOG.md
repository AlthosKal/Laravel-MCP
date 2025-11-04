# Changelog

Todos los cambios notables en este proyecto serán documentados en este archivo.

## [Unreleased] - 2025-01-03

### ✨ Agregado

#### Dependencias
- Integración completa de **pgvector-php** en mcp-rag
  - Trait `HasNeighbors` en `FragmentDocument` model
  - Cast `Vector::class` para columna `embedding`
  - Soporte nativo para queries de vecinos más cercanos

- Integración completa de **php-mcp/client** en mcp-client
  - Reemplazo de Guzzle por cliente MCP nativo
  - `Client::make()` con builder pattern
  - `TransportType::Http` y `ServerConfig`
  - Manejo nativo de respuestas MCP

#### Excepciones Personalizadas
- Sistema completo de excepciones para los 3 proyectos:
  - **mcp-rag**: `RagException`, `DocumentException`, `EmbeddingException`, `SearchException`
  - **mcp-processes**: `ProcessException`, `FormattingException`, `ValidationException`, `TemplateException`
  - **mcp-client**: `ClientException`, `McpConnectionException`, `AiServiceException`, `DocumentServiceException`

#### Manejadores Globales de Excepciones
- Configuración en `bootstrap/app.php` de los 3 proyectos
- Logging automático con contexto completo
- Respuestas JSON/HTML según tipo de request
- Código HTTP correcto según tipo de excepción
- Debug info solo en modo desarrollo

#### Documentación
- `ARCHITECTURE.md` - Arquitectura completa del sistema
- `CHANGELOG.md` - Historial de cambios
- Actualización de README_CHAT.md

### 🔄 Cambiado

#### Refactorización Masiva
- **Eliminación completa de try-catch blocks** en todos los servicios:
  - mcp-rag: 6 archivos modificados
  - mcp-processes: 2 archivos modificados
  - mcp-client: 6 archivos modificados

- **Simplificación de EmbeddingService**
  - Eliminada lógica de retry manual con try-catch
  - Confianza en manejador global de excepciones

#### MCP Tools
- Actualización de estructura en 8 tools (4 en mcp-rag, 4 en mcp-processes):
  - Agregado `protected string $name`
  - Agregado `protected string $title`
  - Mensajes de validación en español
  - Anotaciones correctas (`#[IsIdempotent]`, `#[IsReadOnly]`)

#### Servicios MCP Client
- `McpRagClientService`: Migrado de Guzzle a php-mcp/client
- `McpClientRepository`: Convertido a stub (no hay cliente MCP genérico)
- `DocumentService`: Sin try-catch, delegación al handler global
- `OllamaService`: Sin try-catch en 4 métodos
- `AiAssistantService`: Sin try-catch en processMessage, processMessageStreaming, getAvailableTools

#### Comandos
- `TestServicesCommand`: Eliminada prueba de MCP client, solo prueba Ollama

### ❌ Eliminado

#### McpCalculatorClient
- Eliminado `app/Services/McpCalculatorClient.php`
- Removidas referencias en `AppServiceProvider.php`
- Removidas referencias en documentación
- Eliminados bindings de 'mcp.calculator.http' y 'mcp.calculator.stdio'

#### Try-Catch Blocks
- **14 archivos** con try-catch eliminados completamente:
  - SemanticSearchService.php
  - EmbeddingService.php
  - UploadDocumentTool.php
  - SearchSemanticTool.php
  - DeleteDocumentTool.php
  - GetDocumentVersionsTool.php
  - GenerateTemplateReportTool.php
  - FormatStructuredTool.php
  - OllamaService.php
  - DocumentService.php
  - McpCalculatorClient.php (archivo eliminado)
  - McpRagClientService.php
  - AiAssistantService.php
  - TestServicesCommand.php

### 🔧 Reparado

#### Formateo de Código
- Ejecutado `vendor/bin/pint --dirty` en los 3 proyectos
- **mcp-rag**: 48 archivos formateados ✅
- **mcp-processes**: 35 archivos, 4 issues corregidos ✅
- **mcp-client**: 35 archivos formateados ✅

## Estadísticas

### Archivos Modificados
- **Total**: 25 archivos
- **Eliminados**: 1 archivo (McpCalculatorClient.php)
- **Creados**: 15 archivos (excepciones personalizadas + documentación)

### Líneas de Código
- **Try-catch removidos**: ~350 líneas eliminadas
- **Excepciones agregadas**: ~200 líneas agregadas
- **Documentación**: ~500 líneas agregadas
- **Beneficio neto**: Código más limpio y mantenible

### Cobertura
- **3 proyectos** refactorizados completamente
- **8 MCP tools** actualizados
- **14 servicios/archivos** sin try-catch
- **3 sistemas** de excepciones implementados
- **3 manejadores** globales configurados

## Beneficios

### Mantenibilidad
- ✅ Código más limpio y legible
- ✅ Menos duplicación
- ✅ Centralización del manejo de errores
- ✅ Logging consistente

### SOLID Principles
- ✅ Single Responsibility: Cada servicio tiene una responsabilidad
- ✅ Dependency Inversion: Uso de interfaces y dependency injection
- ✅ Open/Closed: Extensible mediante excepciones personalizadas

### Developer Experience
- ✅ Menos código boilerplate
- ✅ Excepciones con contexto rico
- ✅ Debugging más fácil
- ✅ Stack traces completos en logs

### Uso Correcto de Dependencias
- ✅ pgvector-php para operaciones vectoriales nativas
- ✅ php-mcp/client para comunicación MCP estándar
- ✅ Eliminación de código custom innecesario

## Notas de Migración

### Breaking Changes
- ⚠️ La lógica de retry automático en `EmbeddingService` fue removida
- ⚠️ `McpCalculatorClient` ya no existe, usar `McpRagClientService` para RAG
- ⚠️ Todas las excepciones ahora se propagan al handler global

### Recomendaciones
- Configurar `APP_DEBUG=false` en producción
- Monitorear logs para excepciones no capturadas
- Revisar excepciones personalizadas para casos de negocio específicos

## Próximos Pasos

- [ ] Tests unitarios para excepciones personalizadas
- [ ] Tests de integración E2E
- [ ] Documentación de API con OpenAPI/Swagger
- [ ] Performance benchmarks
- [ ] CI/CD pipeline
