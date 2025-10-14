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
class MultiplyTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'multiply';
    /**
     * The tool's title.
     */
    protected string $title = 'Multiplicar números';
    /**
     * The tool's description.
     */
    protected string $description = 'Multiplica dos números y retorna el resultado.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'a' => ['required', 'numeric'],
            'b' => ['required', 'numeric'],
        ], [
            'a.required' => 'El primer factor (a) es requerido.',
            'a.numeric' => 'El primer factor (a) debe ser un valor numérico.',
            'b.required' => 'El segundo factor (b) es requerido.',
            'b.numeric' => 'El segundo factor (b) debe ser un valor numérico.',
        ]);

        $result = $validated['a'] * $validated['b'];

        return Response::text(sprintf(
            "Operación: Multiplicación\nPrimer factor: %s\nSegundo factor: %s\nResultado: %s",
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
                ->description('El primer factor a multiplicar.')
                ->required(),
            'b' => $schema->number()
                ->description('El segundo factor a multiplicar.')
                ->required(),
        ];
    }
}
