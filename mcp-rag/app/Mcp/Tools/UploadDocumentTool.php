<?php

namespace App\Mcp\Tools;

use App\Contracts\DocumentRepositoryInterface;
use App\Contracts\FragmentRepositoryInterface;
use App\Services\ChunkingService;
use App\Services\EmbeddingService;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Psr\Log\LoggerInterface;

#[IsIdempotent]
class UploadDocumentTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'upload_document';

    /**
     * The tool's title.
     */
    protected string $title = 'Subir documento';

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Sube y procesa un documento de texto, dividiéndolo en fragmentos y generando embeddings para búsqueda semántica.

        Este tool acepta un documento de texto plano, lo divide en chunks inteligentes, genera embeddings vectoriales
        usando OpenAI, y almacena todo en la base de datos con índices HNSW para búsqueda eficiente.
    MARKDOWN;

    public function __construct(
        private readonly DocumentRepositoryInterface $documentRepo,
        private readonly FragmentRepositoryInterface $fragmentRepo,
        private readonly ChunkingService $chunkingService,
        private readonly EmbeddingService $embeddingService,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'document_title' => ['required', 'string', 'max:40'],
            'content' => ['required', 'string'],
            'metadata' => ['nullable', 'array'],
            'create_new_version' => ['nullable', 'boolean'],
        ], [
            'document_title.required' => 'El título del documento es requerido.',
            'document_title.string' => 'El título del documento debe ser una cadena de texto.',
            'document_title.max' => 'El título del documento no puede exceder 40 caracteres.',
            'content.required' => 'El contenido del documento es requerido.',
            'content.string' => 'El contenido del documento debe ser una cadena de texto.',
            'metadata.array' => 'Los metadatos deben ser un objeto JSON.',
            'create_new_version.boolean' => 'El campo create_new_version debe ser verdadero o falso.',
        ]);

        $documentTitle = $validated['document_title'];
        $content = $validated['content'];
        $metadata = $validated['metadata'] ?? [];
        $createNewVersion = $validated['create_new_version'] ?? false;

        // Verificar si existe el documento
        $existingDoc = $this->documentRepo->getLatestVersion($documentTitle);

        if ($existingDoc !== null && ! $createNewVersion) {
            return Response::content([
                ['type' => 'text', 'text' => "Error: El documento '{$documentTitle}' ya existe (versión {$existingDoc->version}). Use create_new_version=true para crear una nueva versión."],
            ]);
        }

        // Crear documento o nueva versión
        if ($createNewVersion && $existingDoc !== null) {
            $document = $this->documentRepo->createNewVersion($documentTitle);
        } else {
            $document = $this->documentRepo->create([
                'document_title' => $documentTitle,
                'metadata' => $metadata,
                'document_path' => 'uploads/'.$documentTitle,
                'valid' => true,
                'version' => 1,
            ]);
        }

        // Dividir en chunks
        $chunks = $this->chunkingService->chunkText($content);

        // Generar embeddings
        $embeddings = $this->embeddingService->generateEmbeddingsBatch($chunks);

        // Crear fragmentos
        $fragments = [];
        foreach ($chunks as $index => $chunkContent) {
            $embeddingVector = $this->embeddingService->embeddingToVector($embeddings[$index]);

            $fragments[] = [
                'id_metadata_document' => $document->id,
                'chunk_index' => $index,
                'content' => $chunkContent,
                'embedding' => $embeddingVector,
            ];
        }

        $fragmentsCreated = $this->fragmentRepo->createBatch($fragments);

        $result = sprintf(
            "✓ Documento procesado exitosamente\n\n".
            "Título: %s\n".
            "ID: %s\n".
            "Versión: %d\n".
            "Fragmentos creados: %d\n".
            "Caracteres totales: %d\n",
            $document->document_title,
            $document->id,
            $document->version,
            $fragmentsCreated,
            strlen($content)
        );

        return Response::text($result);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'document_title' => $schema->string()
                ->description('Título del documento (máximo 40 caracteres)')
                ->required(),
            'content' => $schema->string()
                ->description('Contenido completo del documento en texto plano')
                ->required(),
            'metadata' => $schema->object()
                ->description('Metadatos adicionales del documento (opcional)'),
            'create_new_version' => $schema->boolean()
                ->description('Si existe el documento, crear una nueva versión')
                ->default(false),
        ];
    }
}
