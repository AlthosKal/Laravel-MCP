# Guía de API - CognitiveDocs

## Servidores API Puros

Los servidores **mcp-rag** y **mcp-processes** son APIs puras sin interfaz gráfica. Toda la comunicación se realiza mediante JSON.

## MCP-RAG Server (Puerto 8001)

### Endpoints Base

#### GET `/`
Información del servicio

**Respuesta:**
```json
{
  "service": "MCP-RAG Server",
  "version": "1.0.0",
  "status": "running",
  "endpoints": {
    "mcp": "/mcp/rag",
    "health": "/up"
  }
}
```

#### GET `/up`
Health check del servicio

**Respuesta:** `200 OK`

### MCP Endpoint

#### POST `/mcp/rag`
Endpoint MCP para ejecutar tools

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Formato MCP Request:**
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "tools/call",
  "params": {
    "name": "upload_document",
    "arguments": {
      "document_title": "Manual Laravel",
      "content": "Laravel es un framework...",
      "metadata": {"author": "Taylor"}
    }
  }
}
```

**Formato MCP Response:**
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "result": {
    "content": [
      {
        "type": "text",
        "text": "✓ Documento procesado exitosamente\n\nTítulo: Manual Laravel\nID: uuid-here\nVersión: 1\nFragmentos creados: 15\nCaracteres totales: 5000"
      }
    ],
    "isError": false
  }
}
```

### Tools Disponibles

#### 1. `upload_document`
Sube y procesa un documento con embeddings

**Argumentos:**
```json
{
  "document_title": "string (max 40 chars)",
  "content": "string",
  "metadata": "object (opcional)",
  "create_new_version": "boolean (opcional, default: false)"
}
```

**Respuesta exitosa:**
```
✓ Documento procesado exitosamente

Título: Manual Laravel
ID: 550e8400-e29b-41d4-a716-446655440000
Versión: 1
Fragmentos creados: 15
Caracteres totales: 5000
```

**Errores:**
- `document_title` ya existe y `create_new_version=false`
- `document_title` excede 40 caracteres
- Fallo al generar embeddings (OpenAI API)

---

#### 2. `search_semantic`
Búsqueda semántica por similitud vectorial

**Argumentos:**
```json
{
  "query": "string",
  "limit": "integer (opcional, default: 5, max: 20)",
  "threshold": "float (opcional, default: 0.0, range: 0-1)",
  "document_id": "uuid (opcional)",
  "group_by_document": "boolean (opcional, default: false)"
}
```

**Respuesta (sin agrupación):**
```
🔍 Búsqueda Semántica

Consulta: ¿Cómo funciona Eloquent?
Resultados: 5

📄 Manual Laravel (v1)
   Chunk: 3 | Similitud: 95.2%
   Eloquent ORM es el sistema de mapeo objeto-relacional...

📄 Guía Avanzada (v2)
   Chunk: 7 | Similitud: 87.4%
   Los modelos Eloquent representan tablas...
```

**Respuesta (con agrupación):**
```
🔍 Búsqueda Semántica Agrupada

Consulta: ¿Cómo funciona Eloquent?
Documentos encontrados: 2

📄 Manual Laravel (v1)
   Similitud promedio: 92.3%
   Fragmentos relevantes:

   [3] Similitud: 95.2%
   Eloquent ORM es el sistema...

   [5] Similitud: 89.4%
   Los modelos extienden...
```

---

#### 3. `get_document_versions`
Lista todas las versiones de un documento

**Argumentos:**
```json
{
  "document_title": "string"
}
```

**Respuesta:**
```
📋 Versiones del Documento: Manual Laravel

Total de versiones: 3

✓ Versión 3
   ID: 550e8400-e29b-41d4-a716-446655440003
   Estado: Válido
   Creado: 2025-01-03 10:30:00
   Metadatos: {"author":"Taylor","updated":true}

✓ Versión 2
   ID: 550e8400-e29b-41d4-a716-446655440002
   Estado: Válido
   Creado: 2025-01-02 14:20:00

✗ Versión 1
   ID: 550e8400-e29b-41d4-a716-446655440001
   Estado: Inválido
   Creado: 2025-01-01 09:15:00
```

---

#### 4. `delete_document`
Elimina un documento (soft o hard delete)

**Argumentos:**
```json
{
  "document_id": "uuid",
  "soft_delete": "boolean (opcional, default: false)"
}
```

**Respuesta (soft delete):**
```
✓ Documento marcado como inválido exitosamente

Título: Manual Laravel
ID: 550e8400-e29b-41d4-a716-446655440000
Operación: soft_delete
```

**Respuesta (hard delete):**
```
✓ Documento eliminado permanentemente exitosamente

Título: Manual Laravel
ID: 550e8400-e29b-41d4-a716-446655440000
Operación: hard_delete
```

---

## MCP-Processes Server (Puerto 8002)

### Endpoints Base

#### GET `/`
Información del servicio

**Respuesta:**
```json
{
  "service": "MCP-Processes Server",
  "version": "1.0.0",
  "status": "running",
  "endpoints": {
    "mcp": "/mcp/processes",
    "health": "/up"
  },
  "tools": [
    "format_structured",
    "extract_entities",
    "validate_content",
    "generate_template_report"
  ]
}
```

#### GET `/up`
Health check del servicio

**Respuesta:** `200 OK`

### MCP Endpoint

#### POST `/mcp/processes`
Endpoint MCP para ejecutar tools de procesamiento

**Headers:** Igual que MCP-RAG

### Tools Disponibles

#### 1. `format_structured`
Formatea contenido en estructuras específicas

**Argumentos:**
```json
{
  "content": "string",
  "format": "string (json|xml|yaml|csv)",
  "pretty_print": "boolean (opcional, default: true)"
}
```

**Ejemplo JSON:**
```json
{
  "content": "{\"name\":\"John\",\"age\":30}",
  "format": "json",
  "pretty_print": true
}
```

**Respuesta:**
```json
{
  "name": "John",
  "age": 30
}
```

---

#### 2. `extract_entities`
Extrae entidades del texto (emails, URLs, fechas, etc.)

**Argumentos:**
```json
{
  "content": "string",
  "entity_types": "array (opcional, default: all)",
  "return_positions": "boolean (opcional, default: false)"
}
```

**Entity Types:**
- `email` - Direcciones de email
- `url` - URLs
- `phone` - Números de teléfono
- `date` - Fechas
- `number` - Números
- `currency` - Valores monetarios

**Ejemplo:**
```json
{
  "content": "Contacto: info@example.com, visita https://example.com",
  "entity_types": ["email", "url"]
}
```

**Respuesta:**
```json
{
  "emails": ["info@example.com"],
  "urls": ["https://example.com"],
  "total_found": 2
}
```

---

#### 3. `validate_content`
Valida y limpia contenido de texto

**Argumentos:**
```json
{
  "content": "string",
  "validation_rules": "array (opcional)",
  "clean": "boolean (opcional, default: false)"
}
```

**Validation Rules:**
- `remove_html` - Eliminar tags HTML
- `remove_extra_spaces` - Normalizar espacios
- `trim` - Eliminar espacios al inicio/final
- `lowercase` - Convertir a minúsculas
- `uppercase` - Convertir a mayúsculas

**Ejemplo:**
```json
{
  "content": "  <p>Hello   World</p>  ",
  "validation_rules": ["remove_html", "remove_extra_spaces", "trim"],
  "clean": true
}
```

**Respuesta:**
```
Hello World
```

---

#### 4. `generate_template_report`
Genera reportes usando plantillas predefinidas

**Argumentos:**
```json
{
  "template": "string (summary|detailed|markdown|html)",
  "data": "object",
  "title": "string (opcional, default: 'Reporte Generado')"
}
```

**Ejemplo:**
```json
{
  "template": "markdown",
  "data": {
    "total_users": 1500,
    "active_today": 450,
    "revenue": "$25,000"
  },
  "title": "Reporte Diario"
}
```

**Respuesta (markdown):**
```markdown
# Reporte Diario

_Generado: 2025-01-03 15:30:00_

---

## Total Users

- **total_users**: 1500

## Active Today

- **active_today**: 450

## Revenue

- **revenue**: $25,000
```

---

## Manejo de Errores

Todos los servidores usan un **manejador global de excepciones** que retorna JSON consistente:

### Respuesta de Error
```json
{
  "success": false,
  "error": "Mensaje de error descriptivo",
  "exception": "App\\Exceptions\\DocumentException",
  "trace": "Stack trace completo (solo en debug mode)"
}
```

### Códigos HTTP Comunes
- `200` - Éxito
- `400` - Error de validación
- `404` - Recurso no encontrado
- `422` - Error de validación de entrada
- `500` - Error interno del servidor

### Ejemplos de Errores

**Validación:**
```json
{
  "success": false,
  "error": "El título del documento es requerido.",
  "exception": "Illuminate\\Validation\\ValidationException"
}
```

**Documento no encontrado:**
```json
{
  "success": false,
  "error": "❌ Documento no encontrado",
  "exception": null
}
```

**Error de OpenAI:**
```json
{
  "success": false,
  "error": "Failed to generate embedding: API rate limit exceeded",
  "exception": "App\\Exceptions\\EmbeddingException"
}
```

---

## Cliente PHP (php-mcp/client)

### Ejemplo de Uso

```php
use PhpMcp\Client\Client;
use PhpMcp\Client\Enum\TransportType;
use PhpMcp\Client\ServerConfig;

// Configurar cliente
$serverConfig = new ServerConfig(
    name: 'rag_server',
    transport: TransportType::Http,
    timeout: 30.0,
    url: 'http://mcp-rag:8001/mcp/rag',
);

$client = Client::make()
    ->withClientInfo('MyApp', '1.0.0')
    ->withServerConfig($serverConfig)
    ->build();

// Inicializar
$client->initialize();

// Llamar tool
$result = $client->callTool('upload_document', [
    'document_title' => 'Manual Laravel',
    'content' => 'Laravel es un framework...',
]);

// Procesar respuesta
if (!$result->isError) {
    echo $result->content[0]['text'];
} else {
    echo "Error: " . $result->content[0]['text'];
}

// Desconectar
$client->disconnect();
```

---

## Testing con cURL

### Upload Document
```bash
curl -X POST http://localhost:8001/mcp/rag \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "tools/call",
    "params": {
      "name": "upload_document",
      "arguments": {
        "document_title": "Test Doc",
        "content": "Contenido de prueba"
      }
    }
  }'
```

### Search Semantic
```bash
curl -X POST http://localhost:8001/mcp/rag \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 2,
    "method": "tools/call",
    "params": {
      "name": "search_semantic",
      "arguments": {
        "query": "¿Qué es Laravel?",
        "limit": 3
      }
    }
  }'
```

### Format Structured
```bash
curl -X POST http://localhost:8002/mcp/processes \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "id": 3,
    "method": "tools/call",
    "params": {
      "name": "format_structured",
      "arguments": {
        "content": "{\"name\":\"John\"}",
        "format": "json",
        "pretty_print": true
      }
    }
  }'
```

---

## Arquitectura sin Frontend

### mcp-rag
- ❌ Sin `resources/js`
- ❌ Sin `resources/css`
- ❌ Sin `resources/views/welcome.blade.php`
- ❌ Sin `package.json`, `vite.config.js`, etc.
- ✅ Solo rutas API y MCP
- ✅ Respuestas JSON puras

### mcp-processes
- ❌ Sin frontend (igual que mcp-rag)
- ✅ Solo procesamiento de texto
- ✅ Respuestas JSON puras

### mcp-client
- ✅ **Único con frontend** (Vue.js + Tailwind)
- ✅ Interfaz de chat
- ✅ Integración con Ollama/Mistral
- ✅ Cliente de los otros 2 servidores

---

## Configuración de Producción

### Variables de Entorno

**mcp-rag (.env):**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://rag.yourdomain.com

DB_CONNECTION=pgsql
DB_HOST=database-rag
DB_PORT=5432
DB_DATABASE=rag_db
DB_USERNAME=rag_user
DB_PASSWORD=secure_password

REDIS_HOST=database-cache
REDIS_PASSWORD=null
REDIS_PORT=6379

OPENAI_API_KEY=sk-your-production-key

QUEUE_CONNECTION=redis
LOG_CHANNEL=stack
LOG_LEVEL=error
```

**mcp-processes (.env):**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://processes.yourdomain.com

LOG_CHANNEL=stack
LOG_LEVEL=error
```

### Nginx Configuration

```nginx
# MCP-RAG
server {
    listen 80;
    server_name rag.yourdomain.com;

    location / {
        proxy_pass http://mcp-rag:8001;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}

# MCP-Processes
server {
    listen 80;
    server_name processes.yourdomain.com;

    location / {
        proxy_pass http://mcp-processes:8002;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

---

## Logging y Monitoreo

Todos los logs se almacenan en `storage/logs/laravel.log` con formato estructurado:

```json
{
  "message": "Error al generar embedding",
  "exception": "App\\Exceptions\\EmbeddingException",
  "file": "/app/Services/EmbeddingService.php",
  "line": 35,
  "trace": "..."
}
```

**Ver logs en tiempo real:**
```bash
# mcp-rag
cd mcp-rag && php artisan pail

# mcp-processes
cd mcp-processes && php artisan pail
```

---

## Soporte

- GitHub: https://github.com/tu-usuario/cognitivedocs
- Documentación Laravel: https://laravel.com/docs
- MCP Spec: https://spec.modelcontextprotocol.io
- pgvector: https://github.com/pgvector/pgvector
