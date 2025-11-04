<?php

namespace App\Mcp\Tools;

use App\Contracts\DocumentRepositoryInterface;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetDocumentVersionsTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'get_document_versions';

    /**
     * The tool's title.
     */
    protected string $title = 'Obtener versiones de documento';

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Obtiene todas las versiones disponibles de un documento.

        Este tool lista todas las versiones de un documento dado su título, mostrando
        información de cada versión incluyendo su estado de validez y metadatos.
    MARKDOWN;

    public function __construct(
        private readonly DocumentRepositoryInterface $documentRepo
    ) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'document_title' => ['required', 'string'],
        ], [
            'document_title.required' => 'El título del documento es requerido.',
            'document_title.string' => 'El título del documento debe ser una cadena de texto.',
        ]);

        $documentTitle = $validated['document_title'];
        $versions = $this->documentRepo->getAllVersions($documentTitle);

        if ($versions->isEmpty()) {
            return Response::text("No se encontró el documento '{$documentTitle}'");
        }

        $text = "📋 Versiones del Documento: {$documentTitle}\n\n";
        $text .= 'Total de versiones: '.$versions->count()."\n\n";

        foreach ($versions as $doc) {
            $status = $doc->valid ? '✓' : '✗';
            $text .= "{$status} Versión {$doc->version}\n";
            $text .= "   ID: {$doc->id}\n";
            $text .= '   Estado: '.($doc->valid ? 'Válido' : 'Inválido')."\n";
            $text .= "   Creado: {$doc->created_at->format('Y-m-d H:i:s')}\n";

            if (! empty($doc->metadata)) {
                $text .= '   Metadatos: '.json_encode($doc->metadata)."\n";
            }

            $text .= "\n";
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
            'document_title' => $schema->string()
                ->description('Título del documento')
                ->required(),
        ];
    }
}
