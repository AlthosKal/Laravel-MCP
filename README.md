# 🧠 CognitiveDocs - Sistema RAG con MCP y Laravel

## Descripción del Proyecto

Sistema completo de análisis documental usando **Laravel**, **MCP (Model Context Protocol)**, **PostgreSQL con pgvector**, y **Vue.js**. El sistema consta de 3 servicios containerizados con Mistral integrado en el cliente principal.

---

## 🏗 Arquitectura del Sistema

El sistema se compone de 3 microservicios Laravel:

### 1. MCP-RAG Server (Puerto 8001)
**Servidor API puro especializado en búsqueda semántica y gestión de documentos**

- ✅ **API Only** - Sin interfaz gráfica, respuestas JSON
- ✅ Subida y procesamiento de documentos
- ✅ Chunking inteligente de texto (500-1000 tokens)
- ✅ Generación de embeddings con OpenAI (text-embedding-3-small)
- ✅ Búsqueda semántica por similitud coseno
- ✅ Gestión de versiones de documentos
- ✅ Base de datos PostgreSQL con pgvector
- ✅ Índices HNSW para búsqueda eficiente
- ✅ Manejador global de excepciones con logging automático

**MCP Tools Implementados:**
- `upload_document` - Procesar y almacenar documento con embeddings
- `search_semantic` - Búsqueda por similitud vectorial
- `get_document_versions` - Listar versiones disponibles
- `delete_document` - Eliminación con cascada (soft/hard delete)

### 2. MCP-Processes Server (Puerto 8002)
**Servidor API puro auxiliar para procesamiento de texto**

- ✅ **API Only** - Sin interfaz gráfica, respuestas JSON
- ✅ Manejador global de excepciones

**MCP Tools Implementados:**
- `format_structured` - Formatear texto en JSON/XML/YAML/CSV
- `extract_entities` - Extraer emails, URLs, fechas, números, etc.
- `validate_content` - Validar y limpiar texto
- `generate_template_report` - Generar reportes con plantillas

### 3. MCP-Client (Puerto 8000)
**Cliente principal con interfaz Vue.js + integración Mistral**

- ✅ Cliente MCP para conectar con RAG y Processes servers
- ✅ Integración con Ollama/Mistral para generación
- ✅ Streaming de respuestas en tiempo real
- ✅ Interface Vue.js para chat
- ✅ Gestión de historial de conversación

---

## 📊 Estructura de Base de Datos

### Tabla: `metadata_documents`
```sql
- id (UUID, PRIMARY KEY)
- document_title (VARCHAR 40)
- metadata (JSONB)
- document_path (VARCHAR 50)
- valid (BOOLEAN)
- version (INTEGER)
- created_at, updated_at
```

**Índices:**
- `idx_docs_title` en `document_title`
- `idx_docs_valid` en `valid`
- `idx_docs_title_version` en `(document_title, version)`

### Tabla: `fragment_documents`
```sql
- id (SERIAL, PRIMARY KEY)
- id_metadata_document (UUID, FOREIGN KEY)
- chunk_index (INTEGER)
- content (TEXT)
- embedding (VECTOR(1536))
- created_at, updated_at
```

**Índices:**
- `idx_fragments_doc` en `id_metadata_document`
- `idx_fragments_doc_chunk` en `(id_metadata_document, chunk_index)`
- `fragment_documents_embedding_idx` (HNSW) para búsqueda vectorial

---

## 🚀 Componentes Implementados

### MCP-RAG Server

#### Modelos Eloquent
- ✅ `MetadataDocument` - Gestión de documentos con UUIDs
- ✅ `FragmentDocument` - Fragmentos con embeddings vectoriales

#### Servicios Core
- ✅ `ChunkingService` - División inteligente de texto con overlap
- ✅ `EmbeddingService` - Generación de embeddings con OpenAI
  - Rate limiting (3000 RPM)
  - Retry con backoff exponencial
  - Batch processing
- ✅ `SemanticSearchService` - Búsqueda por similitud
  - Similitud coseno con pgvector
  - Caché de 1 hora
  - Agrupación por documento

#### Repositorios (Pattern Repository)
- ✅ `DocumentRepository` - CRUD de documentos
- ✅ `FragmentRepository` - CRUD de fragmentos

#### MCP Tools
- ✅ 4 tools implementados con estructura correcta
- ✅ Validación de entrada con Laravel
- ✅ Respuestas formateadas con Response::text()

### MCP-Processes Server

#### MCP Tools
- ✅ 4 tools de procesamiento de texto
- ✅ Formato múltiple (JSON, XML, YAML, CSV)
- ✅ Extracción de entidades con regex
- ✅ Validación y limpieza de contenido

### MCP-Client

#### Servicios
- ✅ `OllamaService` - Integración con Mistral
  - Streaming de respuestas
  - Análisis de intención para usar tools
  - Timeout y retry

#### Controladores
- ✅ `AiChatController` - Endpoints de chat
  - Streaming con SSE (Server-Sent Events)
  - Listado de tools disponibles

---

## 🐳 Docker Compose

El sistema incluye:
- ✅ PostgreSQL con pgvector, pg_http, pg_cron
- ✅ Redis para cache y queues
- ✅ Ollama con Mistral (descarga automática del modelo)
- ✅ 3 aplicaciones Laravel
- ✅ Networking personalizado `laravel-network`
- ✅ Health checks para todos los servicios

---

## ⚙ Configuración

### 1. Variables de Entorno

**MCP-RAG (.env)**
```bash
OPENAI_API_KEY=sk-your-api-key
DB_CONNECTION=pgsql
DB_HOST=database-rag
DB_PORT=5432
REDIS_HOST=database-cache
QUEUE_CONNECTION=redis
```

### 2. Iniciar el Sistema

```bash
# 1. Iniciar servicios Docker
docker-compose up -d

# 2. Ejecutar migraciones en MCP-RAG
docker exec mcp-rag php artisan migrate

# 3. Verificar que Ollama descargó Mistral
docker logs mistral-model-setup

# 4. Iniciar servidores en desarrollo (opcional, sin UI)
# MCP-RAG:
cd mcp-rag && composer run dev

# MCP-Processes:
cd mcp-processes && composer run dev

# MCP-Client (con Vue.js):
cd mcp-client && composer run dev
```

---

## 📝 Uso del Sistema

### 1. Subir un Documento (vía MCP)

```json
{
  "tool": "upload_document",
  "arguments": {
    "document_title": "Manual Laravel",
    "content": "Laravel es un framework PHP...",
    "metadata": {"author": "Taylor Otwell"}
  }
}
```

### 2. Buscar Semánticamente

```json
{
  "tool": "search_semantic",
  "arguments": {
    "query": "¿Cómo funciona Eloquent ORM?",
    "limit": 5
  }
}
```

### 3. Flujo RAG + Mistral

1. Usuario envía pregunta al cliente
2. Cliente consulta `search_semantic` en MCP-RAG
3. Cliente obtiene fragmentos relevantes
4. Cliente combina contexto + pregunta
5. Cliente envía a Mistral para generar respuesta
6. Cliente devuelve respuesta con referencias

---

## 🔒 Patrones Implementados

- ✅ **Repository Pattern** - Abstracción de acceso a datos
- ✅ **Service Layer** - Lógica de negocio encapsulada
- ✅ **Dependency Injection** - Constructor injection
- ✅ **SOLID Principles** - Single Responsibility, Interface Segregation
- ✅ **Retry with Exponential Backoff** - OpenAI API calls
- ✅ **Caching** - Redis para búsquedas semánticas

---

## 📚 Endpoints MCP

### MCP-RAG Server
- HTTP: `http://mcp-rag:8001/mcp/rag`
- Local: `rag` (para CLI)

### MCP-Processes Server
- HTTP: `http://mcp-processes:8002/mcp/processes`
- Local: `processes` (para CLI)

---

## 🧪 Testing

```bash
# Ejecutar tests en MCP-RAG
cd mcp-rag
php artisan test

# Ejecutar tests en MCP-Processes
cd mcp-processes
php artisan test

# Ejecutar tests en MCP-Client
cd mcp-client
php artisan test
```

---

## 📦 Dependencias Clave

### MCP-RAG
- `laravel/mcp` ^0.3.2
- `openai-php/client` ^0.18.0
- PostgreSQL 17 + pgvector

### MCP-Processes
- `laravel/mcp` ^0.3.0

### MCP-Client
- `php-mcp/client` ^1.0
- `guzzlehttp/guzzle` ^7.10
- Vue.js 3

---

## 🎯 Características Destacadas

1. **Búsqueda Semántica Eficiente**
   - Índices HNSW en pgvector
   - Embeddings de 1536 dimensiones
   - Similitud coseno optimizada

2. **Chunking Inteligente**
   - División por párrafos y oraciones
   - Overlap configurable
   - Estimación de tokens

3. **Gestión de Versiones**
   - Múltiples versiones por documento
   - Soft delete / Hard delete
   - Historial completo

4. **Rate Limiting**
   - Respeto a límites de OpenAI (3000 RPM)
   - Retry automático con backoff
   - Batch processing eficiente

5. **Streaming en Tiempo Real**
   - SSE para respuestas progresivas
   - Integración con Ollama
   - Vue.js reactivo

---

## 🔧 Próximos Pasos

Para completar la integración total:

1. **Frontend Vue.js**
   - Interfaz de subida de documentos (drag & drop)
   - Selector de versiones
   - Visualización de contexto RAG

2. **Cliente MCP en mcp-client**
   - `McpRagClientService` para conectar con RAG server
   - `DocumentService` para gestión de documentos
   - Actualizar `AiAssistantService` con flujo RAG completo

3. **Configuración de Producción**
   - Variables de entorno para todos los servicios
   - Configuración de Nginx
   - SSL/TLS

---

## 📞 Soporte

- Documentación Laravel: https://laravel.com/docs
- Documentación MCP: https://docs.claude.com/mcp
- pgvector: https://github.com/pgvector/pgvector
- OpenAI API: https://platform.openai.com/docs

---

## 📄 Licencia

MIT License - Ver LICENSE para más detalles.

---

**Desarrollado con ❤️ usando Laravel 12, MCP, PostgreSQL y Vue.js**
