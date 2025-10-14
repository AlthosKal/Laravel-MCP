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
class DivideTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'divide';

    /**
     * The tool's title.
     */
    protected string $title = 'Dividir números';

    /**
     * The tool's description.
     */
    protected string $description = 'Divide dos números y retorna el resultado.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'a' => ['required', 'numeric'],
            'b' => ['required', 'numeric', 'not_in:0'],
        ], [
            'a.required' => 'El dividendo (a) es requerido.',
            'a.numeric' => 'El dividendo (a) debe ser un valor numérico.',
            'b.required' => 'El divisor (b) es requerido.',
            'b.numeric' => 'El divisor (b) debe ser un valor numérico.',
            'b.not_in' => 'No se puede dividir por cero. Por favor proporciona un divisor diferente de 0.',
        ]);

        $result = $validated['a'] / $validated['b'];

        return Response::text(sprintf(
            "Operación: División\nDividendo: %s\nDivisor: %s\nResultado: %s",
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
                ->description('El dividendo (número a dividir).')
                ->required(),
            'b' => $schema->number()
                ->description('El divisor (número por el cual se divide). No puede ser 0.')
                ->required(),
        ];
    }
}
