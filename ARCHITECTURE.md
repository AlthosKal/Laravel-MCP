# 🏗️ Arquitectura del Sistema CognitiveDocs

## Visión General

CognitiveDocs es un sistema distribuido de análisis documental basado en microservicios Laravel que implementa RAG (Retrieval-Augmented Generation) usando MCP (Model Context Protocol).

## Principios de Diseño

### 1. SOLID Principles

- **Single Responsibility**: Cada servicio tiene una responsabilidad única
- **Open/Closed**: Extensible mediante interfaces sin modificar código existente
- **Liskov Substitution**: Implementaciones intercambiables vía interfaces
- **Interface Segregation**: Interfaces específicas y cohesivas
- **Dependency Inversion**: Dependencia de abstracciones, no implementaciones

### 2. Patrones Implementados

- **Repository Pattern**: Abstracción del acceso a datos
- **Service Layer**: Lógica de negocio encapsulada
- **Facade Pattern**: `AiAssistantService` como coordinador
- **Dependency Injection**: Constructor injection en todos los servicios
- **Factory Pattern**: Creación de clientes MCP

## Arquitectura de 3 Capas

```
┌─────────────────────────────────────────────────────────────┐
│                     MCP-CLIENT (Puerto 8000)                │
│  ┌─────────────┐  ┌──────────────┐  ┌──────────────────┐  │
│  │  Vue.js UI  │→ │ Controllers  │→ │ AiAssistant      │  │
│  │  + Tailwind │  │   (HTTP)     │  │   Service        │  │
│  └─────────────┘  └──────────────┘  └──────────────────┘  │
│                                              ↓              │
│                    ┌─────────────────────────┴──────┐      │
│                    ↓                                ↓       │
│         ┌──────────────────┐            ┌─────────────────┐│
│         │ OllamaService    │            │ McpRagClient    ││
│         │  (Mistral LLM)   │            │   Service       ││
│         └──────────────────┘            └─────────────────┘│
└─────────────────────────────────────────────────────────────┘
                                    ↓
        ┌───────────────────────────┴──────────────────────┐
        ↓                                                   ↓
┌──────────────────────┐                    ┌──────────────────────┐
│ MCP-RAG (8001)       │                    │ MCP-PROCESSES (8002) │
│ ┌──────────────────┐ │                    │ ┌──────────────────┐ │
│ │ MCP Tools:       │ │                    │ │ MCP Tools:       │ │
│ │ - upload_doc     │ │                    │ │ - format         │ │
│ │ - search_semantic│ │                    │ │ - extract        │ │
│ │ - get_versions   │ │                    │ │ - validate       │ │
│ │ - delete_doc     │ │                    │ │ - generate       │ │
│ └──────────────────┘ │                    │ └──────────────────┘ │
│         ↓             │                    └──────────────────────┘
│ ┌──────────────────┐ │
│ │ Services:        │ │
│ │ - Chunking       │ │
│ │ - Embedding      │ │
│ │ - Semantic       │ │
│ │   Search         │ │
│ └──────────────────┘ │
│         ↓             │
│ ┌──────────────────┐ │
│ │ PostgreSQL       │ │
│ │ + pgvector       │ │
│ │ + HNSW Index     │ │
│ └──────────────────┘ │
└──────────────────────┘
```

## Flujo de Datos RAG

### 1. Subida de Documento

```
Usuario → MCP-Client → McpRagClientService
                ↓
        upload_document tool
                ↓
          MCP-RAG Server
                ↓
        ChunkingService (divide texto)
                ↓
        EmbeddingService (OpenAI)
                ↓
        PostgreSQL + pgvector
```

### 2. Consulta con RAG

```
Usuario pregunta → MCP-Client
        ↓
AiAssistantService
        ↓
McpRagClientService.searchSemantic()
        ↓
MCP-RAG Server (search_semantic tool)
        ↓
SemanticSearchService
        ↓
PostgreSQL (consulta vectorial con HNSW)
        ↓
Fragmentos relevantes → AiAssistantService
        ↓
Contexto + Pregunta → OllamaService (Mistral)
        ↓
Respuesta generada → Usuario
```

## Gestión de Excepciones

### Enfoque Centralizado

Todos los servicios eliminaron bloques try-catch individuales a favor de:

1. **Manejador Global** en `bootstrap/app.php`
2. **Excepciones Personalizadas** con contexto
3. **Logging Automático** de todas las excepciones
4. **Respuestas Consistentes** (JSON/HTML según contexto)

```php
// ❌ ANTES (try-catch en cada método)
public function search($query) {
    try {
        return $this->db->query(...);
    } catch (\Exception $e) {
        $this->logger->error(...);
        throw $e;
    }
}

// ✅ AHORA (propagación al handler global)
public function search($query) {
    return $this->db->query(...);
    // Excepciones manejadas globalmente
}
```

### Jerarquía de Excepciones

**mcp-rag:**
- `RagException` (base)
  - `DocumentException`
  - `EmbeddingException`
  - `SearchException`

**mcp-processes:**
- `ProcessException` (base)
  - `FormattingException`
  - `ValidationException`
  - `TemplateException`

**mcp-client:**
- `ClientException` (base)
  - `McpConnectionException`
  - `AiServiceException`
  - `DocumentServiceException`

## Tecnologías Clave

### Backend
- **Laravel 12**: Framework PHP moderno
- **PostgreSQL 17**: Base de datos relacional
- **pgvector**: Extensión para búsqueda vectorial
- **pgvector-php**: Cliente Laravel nativo (trait `HasNeighbors`, cast `Vector`)
- **php-mcp/client**: Cliente MCP nativo para PHP
- **OpenAI PHP**: Generación de embeddings
- **Redis**: Cache y queues

### Frontend
- **Vue.js 3**: Framework reactivo
- **Tailwind CSS v4**: Estilos modernos
- **Server-Sent Events**: Streaming en tiempo real

### IA y ML
- **Ollama**: Servidor de modelos locales
- **Mistral**: Modelo LLM
- **OpenAI text-embedding-3-small**: Embeddings (1536 dim)

## Optimizaciones Implementadas

### 1. Búsqueda Vectorial

```sql
-- Índice HNSW para búsqueda rápida
CREATE INDEX ON fragment_documents
USING hnsw (embedding vector_cosine_ops)
WITH (m = 16, ef_construction = 64);
```

### 2. Caché de Búsquedas

```php
Cache::remember($cacheKey, 3600, function() {
    return $this->search($query, $limit);
});
```

### 3. Batch Processing

```php
// Procesa hasta 100 textos por lote
$embeddings = $this->embeddingService
    ->generateEmbeddingsBatch($chunks);
```

### 4. pgvector-php Integration

```php
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

class FragmentDocument extends Model {
    use HasNeighbors;

    protected function casts(): array {
        return [
            'embedding' => Vector::class,
        ];
    }
}
```

### 5. php-mcp/client Integration

```php
use PhpMcp\Client\Client;
use PhpMcp\Client\Enum\TransportType;

$client = Client::make()
    ->withClientInfo('LaravelRagClient', '1.0.0')
    ->withCapabilities($clientCapabilities)
    ->withServerConfig($serverConfig)
    ->build();

$result = $client->callTool($toolName, $arguments);
```

## Escalabilidad

### Horizontal Scaling

- Cada servicio puede escalar independientemente
- Sin estado compartido entre instancias
- Cache distribuido con Redis

### Vertical Scaling

- Índices optimizados (HNSW)
- Conexiones a BD con pooling
- Batch processing de embeddings

## Seguridad

- **CORS configurado** para API
- **Validación de entrada** en todos los tools
- **Rate limiting** en llamadas a OpenAI
- **Sanitización** de contenido de usuario
- **Excepciones sin información sensible** en producción

## Monitoreo

- **Logging estructurado** con contexto
- **Métricas de performance** en logs
- **Health checks** en Docker
- **Debug mode** configurable por entorno

## Próximas Mejoras

1. **Autenticación**: Sanctum para API
2. **Autorización**: Políticas para documentos
3. **Webhooks**: Notificaciones de cambios
4. **Analytics**: Dashboard de uso
5. **Tests E2E**: Cobertura completa
6. **CI/CD**: Pipeline automatizado
