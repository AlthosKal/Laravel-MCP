<?php

namespace App\Mcp\Tools;

use App\Services\SemanticSearchService;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class SearchSemanticTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'search_semantic';

    /**
     * The tool's title.
     */
    protected string $title = 'Búsqueda semántica';

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Realiza una búsqueda semántica en los documentos usando similitud vectorial.

        Este tool genera un embedding de la consulta y busca los fragmentos más similares
        usando el operador de distancia coseno de pgvector (<=>).
    MARKDOWN;

    public function __construct(
        private readonly SemanticSearchService $searchService
    ) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'query' => ['required', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
            'threshold' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'document_id' => ['nullable', 'string'],
            'group_by_document' => ['nullable', 'boolean'],
        ], [
            'query.required' => 'La consulta de búsqueda es requerida.',
            'query.string' => 'La consulta debe ser una cadena de texto.',
            'limit.integer' => 'El límite debe ser un número entero.',
            'limit.min' => 'El límite debe ser al menos 1.',
            'limit.max' => 'El límite no puede ser mayor a 20.',
            'threshold.numeric' => 'El threshold debe ser un número.',
            'threshold.min' => 'El threshold debe ser al menos 0.',
            'threshold.max' => 'El threshold no puede ser mayor a 1.',
            'document_id.string' => 'El ID del documento debe ser una cadena de texto.',
            'group_by_document.boolean' => 'El campo group_by_document debe ser verdadero o falso.',
        ]);

        $query = $validated['query'];
        $limit = $validated['limit'] ?? 5;
        $threshold = $validated['threshold'] ?? 0.0;
        $documentId = $validated['document_id'] ?? null;
        $groupByDocument = $validated['group_by_document'] ?? false;

        if ($groupByDocument) {
            $results = $this->searchService->searchGroupedByDocument($query, 3, (int) ($limit / 3) ?: 1);

            $text = "🔍 Búsqueda Semántica Agrupada\n\n";
            $text .= "Consulta: $query\n";
            $text .= 'Documentos encontrados: '.count($results)."\n\n";

            foreach ($results as $doc) {
                $text .= "📄 {$doc['document_title']} (v{$doc['document_version']})\n";
                $text .= '   Similitud promedio: '.($doc['avg_similarity'] * 100)."%\n";
                $text .= "   Fragmentos relevantes:\n\n";

                foreach ($doc['fragments'] as $frag) {
                    $text .= "   [{$frag['chunk_index']}] Similitud: ".($frag['similarity'] * 100)."%\n";
                    $text .= '   '.substr($frag['content'], 0, 150)."...\n\n";
                }
                $text .= "\n";
            }
        } else {
            $results = $this->searchService->search($query, $limit, $threshold, $documentId);

            $text = "🔍 Búsqueda Semántica\n\n";
            $text .= "Consulta: $query\n";
            $text .= 'Resultados: '.count($results)."\n\n";

            foreach ($results as $result) {
                $text .= "📄 {$result['document_title']} (v{$result['document_version']})\n";
                $text .= "   Chunk: {$result['chunk_index']} | Similitud: ".($result['similarity'] * 100)."%\n";
                $text .= '   '.$result['content']."\n\n";
            }
        }

        return Response::text($text);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('La consulta de búsqueda')
                ->required(),
            'limit' => $schema->integer()
                ->description('Número máximo de resultados (default: 5)')
                ->default(5),
            'threshold' => $schema->number()
                ->description('Umbral mínimo de similitud 0-1 (default: 0.0)')
                ->default(0.0),
            'document_id' => $schema->string()
                ->description('Filtrar por ID de documento específico (opcional)'),
            'group_by_document' => $schema->boolean()
                ->description('Agrupar resultados por documento')
                ->default(false),
        ];
    }
}
