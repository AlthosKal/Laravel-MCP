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
class ValidateContentTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'validate_content';

    /**
     * The tool's title.
     */
    protected string $title = 'Validar y limpiar contenido';

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Valida y limpia texto eliminando caracteres especiales, HTML, espacios extra, etc.

        Aplica múltiples operaciones de limpieza y devuelve el texto procesado
        junto con estadísticas del procesamiento realizado.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
            'operations' => ['nullable', 'array'],
            'max_length' => ['nullable', 'integer', 'min:1'],
        ], [
            'content.required' => 'El contenido es requerido.',
            'content.string' => 'El contenido debe ser una cadena de texto.',
            'operations.array' => 'Las operaciones deben ser un arreglo.',
            'max_length.integer' => 'La longitud máxima debe ser un número entero.',
            'max_length.min' => 'La longitud máxima debe ser al menos 1.',
        ]);

        $content = $validated['content'];
        $operations = $validated['operations'] ?? ['strip_html', 'trim_spaces'];
        $maxLength = $validated['max_length'] ?? null;

        $originalLength = strlen($content);
        $cleaned = $content;

        foreach ($operations as $operation) {
            $cleaned = $this->applyOperation($cleaned, $operation);
        }

        $wasTruncated = false;
        if ($maxLength && strlen($cleaned) > $maxLength) {
            $cleaned = substr($cleaned, 0, $maxLength);
            $wasTruncated = true;
        }

        $output = "✓ Contenido Validado y Limpiado\n\n";
        $output .= "Longitud original: {$originalLength} caracteres\n";
        $output .= 'Longitud limpia: '.strlen($cleaned)." caracteres\n";
        $output .= 'Operaciones aplicadas: '.implode(', ', $operations)."\n";
        $output .= 'Truncado: '.($wasTruncated ? 'Sí' : 'No')."\n\n";
        $output .= "Contenido limpio:\n".str_repeat('-', 50)."\n";
        $output .= $cleaned."\n";

        return Response::text($output);
    }

    private function applyOperation(string $content, string $operation): string
    {
        return match ($operation) {
            'strip_html' => strip_tags($content),
            'remove_urls' => preg_replace('/https?:\/\/[^\s]+/', '', $content),
            'trim_spaces' => trim(preg_replace('/\s+/', ' ', $content)),
            'remove_special_chars' => preg_replace('/[^a-zA-Z0-9\s\-_.,!?áéíóúñÁÉÍÓÚÑ]/', '', $content),
            'lowercase' => mb_strtolower($content),
            'uppercase' => mb_strtoupper($content),
            'remove_numbers' => preg_replace('/\d+/', '', $content),
            'remove_punctuation' => preg_replace('/[^\w\s]/', '', $content),
            default => $content,
        };
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
                ->description('El contenido a validar y limpiar')
                ->required(),
            'operations' => $schema->array()
                ->description('Operaciones: strip_html, remove_urls, trim_spaces, remove_special_chars, lowercase, uppercase, remove_numbers, remove_punctuation')
                ->items($schema->string()->enum([
                    'strip_html', 'remove_urls', 'trim_spaces', 'remove_special_chars',
                    'lowercase', 'uppercase', 'remove_numbers', 'remove_punctuation',
                ])),
            'max_length' => $schema->integer()
                ->description('Longitud máxima permitida del texto')
                ->minimum(1),
        ];
    }
}
