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
class AddTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'add';
    /**
     * The tool's title.
     */
    protected string $title = 'Sumar números';
    /**
     * The tool's description.
     */
    protected string $description = 'Suma dos números y retorna el resultado.';

    /**
     * 
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'a' => ['required', 'numeric'],
            'b' => ['required', 'numeric'],
        ], [
            'a.required' => 'El primer número (a) es requerido.',
            'a.numeric' => 'El primer número (a) debe ser un valor numérico.',
            'b.required' => 'El segundo número (b) es requerido.',
            'b.numeric' => 'El segundo número (b) debe ser un valor numérico.',
        ]);

        $result = $validated['a'] + $validated['b'];

        return Response::text(sprintf(
            "Operación: Suma\nPrimer número: %s\nSegundo número: %s\nResultado: %s",
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
                ->description('El primer número a sumar.')
                ->required(),
            'b' => $schema->number()
                ->description('El segundo número a sumar.')
                ->required(),
        ];
    }
}
