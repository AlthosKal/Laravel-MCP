# AI Chat Assistant - MCP Client

Interfaz de chat interactiva que integra **Ollama** (LLM) con **MCP (Model Context Protocol)** para proporcionar capacidades de IA con acceso a herramientas externas.

## 🎯 Características

- ✅ Chat interactivo con IA (Ollama)
- ✅ Integración con MCP Server para operaciones matemáticas
- ✅ Streaming de respuestas en tiempo real (SSE)
- ✅ Interfaz moderna con Vue.js 3 y Tailwind CSS
- ✅ Arquitectura basada en principios SOLID
- ✅ Soporte para múltiples herramientas MCP

## 🏗️ Arquitectura

### Principios SOLID Aplicados

#### 1. **Single Responsibility Principle (SRP)**
- `AiChatController`: Solo maneja peticiones HTTP
- `AiAssistantService`: Solo coordina la lógica de negocio
- `McpClientRepository`: Solo acceso a MCP
- `OllamaRepository`: Solo acceso a Ollama

#### 2. **Open/Closed Principle (OCP)**
- Las interfaces permiten extender funcionalidad sin modificar código existente
- Nuevos servicios de IA pueden agregarse implementando `AiServiceInterface`
- Nuevos clientes MCP pueden agregarse implementando `McpClientInterface`

#### 3. **Liskov Substitution Principle (LSP)**
- Cualquier implementación de `AiServiceInterface` puede sustituir a otra
- Los repositorios son intercambiables vía interfaces

#### 4. **Interface Segregation Principle (ISP)**
- Interfaces específicas para cada servicio (`AiServiceInterface`, `McpClientInterface`)
- No hay métodos innecesarios en las interfaces

#### 5. **Dependency Inversion Principle (DIP)**
- Las dependencias se inyectan via constructores
- Se depende de abstracciones (interfaces), no de implementaciones concretas
- Binding de interfaces en `AppServiceProvider`

### Patrones de Diseño

#### **Repository Pattern**
```
AiAssistantService
    ↓
AiServiceInterface ← OllamaRepository ← OllamaService
McpClientInterface ← McpClientRepository ← McpCalculatorClient
```

#### **Facade Pattern**
`AiAssistantService` actúa como fachada que coordina:
- Análisis de lenguaje natural (Ollama)
- Ejecución de herramientas (MCP)
- Generación de respuestas

## 📦 Estructura de Archivos

```
app/
├── Contracts/                    # Interfaces (DIP)
│   ├── AiServiceInterface.php
│   └── McpClientInterface.php
├── Repositories/                 # Repository Pattern
│   ├── OllamaRepository.php
│   └── McpClientRepository.php
├── Services/                     # Lógica de negocio
│   ├── AiAssistantService.php   # Facade
│   ├── OllamaService.php
│   └── McpCalculatorClient.php
└── Http/Controllers/
    └── AiChatController.php     # Solo HTTP

resources/
├── js/
│   ├── app.js
│   └── components/
│       └── ChatInterface.vue    # Componente principal
└── views/
    └── chat/
        └── index.blade.php

config/
└── services.php                  # Configuración centralizada
```

## 🚀 Instalación

### Prerrequisitos

1. **Ollama** corriendo en `http://localhost:11434`
   ```bash
   ollama run mistral
   ```

2. **Servidor MCP** (mcp-server-test-with-laravel_mcp)
   ```bash
   cd ../mcp-server-test-with-laravel_mcp
   php artisan serve
   ```

### Pasos de Instalación

1. **Instalar dependencias de PHP**
   ```bash
   composer install
   ```

2. **Instalar dependencias de NPM**
   ```bash
   npm install
   ```

3. **Configurar entorno**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configurar variables de entorno en `.env`**
   ```env
   # Ollama
   OLLAMA_BASE_URL=http://localhost:11434
   OLLAMA_MODEL=mistral

   # MCP
   MCP_CALCULATOR_TYPE=stdio
   ```

5. **Compilar assets**
   ```bash
   npm run build
   # O para desarrollo:
   npm run dev
   ```

6. **Ejecutar el servidor**
   ```bash
   php artisan serve
   ```

7. **Abrir en el navegador**
   ```
   http://localhost:8000
   ```

## 🎮 Uso

### Ejemplos de Consultas

**Operaciones Matemáticas:**
- "suma 5 y 3"
- "multiplica 10 por 4"
- "divide 100 entre 5"
- "resta 50 menos 20"

**Conversación General:**
- "¿Qué puedes hacer?"
- "Explícame qué es MCP"
- "¿Qué herramientas tienes disponibles?"

## 🔧 Configuración Avanzada

### Cambiar Modelo de Ollama

En `.env`:
```env
OLLAMA_MODEL=llama2
# O cualquier otro modelo instalado en Ollama
```

### Usar MCP vía HTTP en lugar de STDIO

En `.env`:
```env
MCP_CALCULATOR_TYPE=http
MCP_CALCULATOR_HTTP_URL=http://localhost:8000/mcp/calculator
```

## 🧪 Testing

```bash
php artisan test
```

## 📊 Flujo de Datos

```mermaid
Usuario → ChatInterface.vue
    ↓ (HTTP POST)
AiChatController
    ↓ (inyección)
AiAssistantService
    ↓
    ├─→ OllamaRepository → OllamaService → API Ollama
    │       (analiza intención)
    │
    └─→ McpClientRepository → McpCalculatorClient → MCP Server
            (ejecuta herramienta)
    ↓
Respuesta streaming (SSE)
    ↓
ChatInterface.vue (actualización en tiempo real)
```

## 🔐 Seguridad

- ✅ CSRF Token en todas las peticiones
- ✅ Validación de input en el servidor
- ✅ Timeout configurables para evitar bloqueos
- ✅ Manejo de errores robusto

## 📝 Notas Técnicas

### Server-Sent Events (SSE)

El streaming utiliza SSE en lugar de WebSockets por:
- Simplicidad de implementación
- Soporte nativo en navegadores
- Unidireccional (servidor → cliente) suficiente para este caso

### Gestión de Estado

- Vue 3 Composition API para reactividad
- Estado local en el componente (no se requiere Vuex/Pinia)

## 🐛 Troubleshooting

### Error: "Cliente MCP no está conectado"

**Solución:** Verificar que el servidor MCP esté corriendo:
```bash
cd ../mcp-server-test-with-laravel_mcp
php artisan serve
```

### Error: "El servicio de IA no está disponible"

**Solución:** Verificar que Ollama esté corriendo:
```bash
ollama list
ollama run mistral
```

### Streaming no funciona

**Solución:** Verificar que el servidor no tenga buffering de salida activo.
En `php.ini`:
```ini
output_buffering = Off
```

## 🚀 Próximas Mejoras

- [ ] Soporte para múltiples servidores MCP
- [ ] Historial de conversaciones persistente
- [ ] Exportar conversaciones
- [ ] Temas claro/oscuro
- [ ] Markdown rendering en respuestas
- [ ] Autenticación de usuarios

## 📄 Licencia

MIT

## 👨‍💻 Autor

Desarrollado siguiendo las mejores prácticas de arquitectura de software y principios SOLID.
