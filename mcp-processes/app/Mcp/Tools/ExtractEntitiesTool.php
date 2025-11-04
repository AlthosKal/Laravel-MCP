<?php

namespace App\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsIdempotent]
#[IsReadOnly]
class ExtractEntitiesTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'extract_entities';

    /**
     * The tool's title.
     */
    protected string $title = 'Extraer entidades del texto';

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Extrae entidades específicas del texto como emails, URLs, teléfonos, fechas, números, hashtags y mentions.

        Utiliza expresiones regulares para identificar patrones comunes y devuelve
        todas las ocurrencias únicas encontradas en el texto.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
            'entity_types' => ['nullable', 'array'],
        ], [
            'content.required' => 'El contenido es requerido.',
            'content.string' => 'El contenido debe ser una cadena de texto.',
            'entity_types.array' => 'Los tipos de entidad deben ser un arreglo.',
        ]);

        $content = $validated['content'];
        $entityTypes = $validated['entity_types'] ?? ['email', 'url', 'phone', 'date', 'number'];

        $entities = [];
        $totalFound = 0;

        foreach ($entityTypes as $type) {
            $found = $this->extractEntityType($content, $type);
            $entities[$type] = $found;
            $totalFound += count($found);
        }

        $output = "🔍 Entidades Extraídas\n\n";
        $output .= "Total encontrado: {$totalFound}\n\n";

        foreach ($entities as $type => $items) {
            if (! empty($items)) {
                $output .= ucfirst($type).' ('.count($items)."):\n";
                foreach ($items as $item) {
                    $output .= "  • {$item}\n";
                }
                $output .= "\n";
            }
        }

        return Response::text($output);
    }

    private function extractEntityType(string $content, string $type): array
    {
        $pattern = match ($type) {
            'email' => '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
            'url' => '/https?:\/\/[^\s]+/',
            'phone' => '/(?:\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/',
            'date' => '/\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}|\d{4}[-\/]\d{1,2}[-\/]\d{1,2}/',
            'number' => '/\b\d+(?:\.\d+)?\b/',
            'hashtag' => '/#[a-zA-Z0-9_]+/',
            'mention' => '/@[a-zA-Z0-9_]+/',
            default => null,
        };

        if ($pattern === null) {
            return [];
        }

        preg_match_all($pattern, $content, $matches);

        return array_unique($matches[0]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'content' => $schema->string()
                ->description('El texto del cual extraer entidades')
                ->required(),
            'entity_types' => $schema->array()
                ->description('Tipos de entidades a extraer: email, url, phone, date, number, hashtag, mention')
                ->items($schema->string()->enum(['email', 'url', 'phone', 'date', 'number', 'hashtag', 'mention'])),
        ];
    }
}
