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
class SubstractTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'subtract';
    /**
     * The tool's title.
     */
    protected string $title = 'Restar números';
    /**
     * The tool's description.
     */
    protected string $description = 'Resta dos números y retorna el resultado.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'a' => ['required', 'numeric'],
            'b' => ['required', 'numeric'],
        ], [
            'a.required' => 'El minuendo (a) es requerido.',
            'a.numeric' => 'El minuendo (a) debe ser un valor numérico.',
            'b.required' => 'El sustraendo (b) es requerido.',
            'b.numeric' => 'El sustraendo (b) debe ser un valor numérico.',
        ]);

        $result = $validated['a'] - $validated['b'];

        return Response::text(sprintf(
            "Operación: Resta\nMinuendo: %s\nSustraendo: %s\nResultado: %s",
            $validated['a'],
            $validated['b'],
            $result
        ));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'a' => $schema->number()
                ->description('El minuendo (número del cual se resta).')
                ->required(),
            'b' => $schema->number()
                ->description('El sustraendo (número que se resta).')
                ->required(),
        ];
    }
}
