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
class FormatStructuredTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'format_structured';

    /**
     * The tool's title.
     */
    protected string $title = 'Formatear datos estructurados';

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Formatea texto en estructuras específicas como JSON, XML, YAML o CSV.

        Este tool valida y limpia el formato, asegurando que el output sea válido
        y bien formateado según el estándar solicitado.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
            'format' => ['required', 'string', 'in:json,xml,yaml,csv'],
            'pretty_print' => ['nullable', 'boolean'],
        ], [
            'content.required' => 'El contenido es requerido.',
            'content.string' => 'El contenido debe ser una cadena de texto.',
            'format.required' => 'El formato es requerido.',
            'format.string' => 'El formato debe ser una cadena de texto.',
            'format.in' => 'El formato debe ser uno de: json, xml, yaml, csv.',
            'pretty_print.boolean' => 'El campo pretty_print debe ser verdadero o falso.',
        ]);

        $content = $validated['content'];
        $format = strtolower($validated['format']);
        $prettyPrint = $validated['pretty_print'] ?? true;

        $result = match ($format) {
            'json' => $this->formatAsJson($content, $prettyPrint),
            'xml' => $this->formatAsXml($content, $prettyPrint),
            'yaml' => $this->formatAsYaml($content),
            'csv' => $this->formatAsCsv($content),
        };

        return Response::text($result);
    }

    private function formatAsJson(string $content, bool $prettyPrint): string
    {
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $decoded = ['content' => $content];
        }

        $options = $prettyPrint ? JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE : 0;

        return json_encode($decoded, $options);
    }

    private function formatAsXml(string $content, bool $prettyPrint): string
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);

        if ($xml === false) {
            $xml = new \SimpleXMLElement('<root/>');
            $xml->addChild('content', htmlspecialchars($content));
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = $prettyPrint;
        $dom->loadXML($xml->asXML());

        return $dom->saveXML();
    }

    private function formatAsYaml(string $content): string
    {
        $lines = explode("\n", $content);
        $yaml = "content: |\n";

        foreach ($lines as $line) {
            $yaml .= '  '.$line."\n";
        }

        return $yaml;
    }

    private function formatAsCsv(string $content): string
    {
        $lines = array_filter(explode("\n", $content));

        return implode("\n", array_map(
            fn ($line) => '"'.str_replace('"', '""', trim($line)).'"',
            $lines
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
            'content' => $schema->string()
                ->description('El contenido a formatear')
                ->required(),
            'format' => $schema->string()
                ->description('Formato de salida: json, xml, yaml, csv')
                ->enum(['json', 'xml', 'yaml', 'csv'])
                ->required(),
            'pretty_print' => $schema->boolean()
                ->description('Formatear con indentación legible')
                ->default(true),
        ];
    }
}
