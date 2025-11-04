<?php

namespace App\Mcp\Tools;

use App\Contracts\DocumentRepositoryInterface;
use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Psr\Log\LoggerInterface;

#[IsIdempotent]
class DeleteDocumentTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'delete_document';

    /**
     * The tool's title.
     */
    protected string $title = 'Eliminar documento';

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Elimina un documento y todos sus fragmentos (operación permanente).

        Este tool puede hacer soft delete (marcar como inválido) o hard delete (eliminación permanente).
        La eliminación cascada automáticamente elimina todos los fragmentos asociados.
    MARKDOWN;

    public function __construct(
        private readonly DocumentRepositoryInterface $documentRepo,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'document_id' => ['required', 'string'],
            'soft_delete' => ['nullable', 'boolean'],
        ], [
            'document_id.required' => 'El ID del documento es requerido.',
            'document_id.string' => 'El ID del documento debe ser una cadena de texto.',
            'soft_delete.boolean' => 'El campo soft_delete debe ser verdadero o falso.',
        ]);

        $documentId = $validated['document_id'];
        $softDelete = $validated['soft_delete'] ?? false;

        $document = $this->documentRepo->findById($documentId);

        if ($document === null) {
            return Response::text('❌ Documento no encontrado');
        }

        if ($softDelete) {
            $result = $this->documentRepo->markAsInvalid($documentId);
            $operation = 'marcado como inválido';
        } else {
            $result = $this->documentRepo->delete($documentId);
            $operation = 'eliminado permanentemente';
        }

        if ($result) {
            $this->logger->info('Documento eliminado', [
                'document_id' => $documentId,
                'title' => $document->document_title,
                'soft_delete' => $softDelete,
            ]);

            return Response::text(
                "✓ Documento {$operation} exitosamente\n\n".
                "Título: {$document->document_title}\n".
                "ID: {$documentId}\n".
                'Operación: '.($softDelete ? 'soft_delete' : 'hard_delete')
            );
        }

        return Response::text('❌ No se pudo eliminar el documento');
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'document_id' => $schema->string()
                ->description('ID del documento a eliminar')
                ->required(),
            'soft_delete' => $schema->boolean()
                ->description('Si es true, solo marca como inválido en lugar de eliminar')
                ->default(false),
        ];
    }
}
