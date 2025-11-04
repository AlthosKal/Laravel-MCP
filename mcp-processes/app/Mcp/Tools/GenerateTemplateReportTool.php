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
class GenerateTemplateReportTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'generate_template_report';

    /**
     * The tool's title.
     */
    protected string $title = 'Generar reporte con plantilla';

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Genera reportes formateados usando plantillas predefinidas.

        Soporta múltiples formatos: summary (texto plano), detailed (con secciones),
        markdown (formato MD), y html (tabla HTML).
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'template' => ['required', 'string', 'in:summary,detailed,markdown,html'],
            'data' => ['required', 'array'],
            'title' => ['nullable', 'string'],
        ], [
            'template.required' => 'La plantilla es requerida.',
            'template.string' => 'La plantilla debe ser una cadena de texto.',
            'template.in' => 'La plantilla debe ser una de: summary, detailed, markdown, html.',
            'data.required' => 'Los datos son requeridos.',
            'data.array' => 'Los datos deben ser un arreglo.',
            'title.string' => 'El título debe ser una cadena de texto.',
        ]);

        $template = $validated['template'];
        $data = $validated['data'];
        $title = $validated['title'] ?? 'Reporte Generado';

        $report = match ($template) {
            'summary' => $this->generateSummaryReport($title, $data),
            'detailed' => $this->generateDetailedReport($title, $data),
            'markdown' => $this->generateMarkdownReport($title, $data),
            'html' => $this->generateHtmlReport($title, $data),
        };

        return Response::text($report);
    }

    private function generateSummaryReport(string $title, array $data): string
    {
        $report = "=== {$title} ===\n\n";
        $report .= 'Fecha: '.now()->format('Y-m-d H:i:s')."\n\n";

        foreach ($data as $key => $value) {
            $label = ucfirst(str_replace('_', ' ', $key));
            $valueStr = is_array($value) ? json_encode($value) : $value;
            $report .= "{$label}: {$valueStr}\n";
        }

        return $report;
    }

    private function generateDetailedReport(string $title, array $data): string
    {
        $report = str_repeat('=', 60)."\n";
        $report .= "{$title}\n";
        $report .= str_repeat('=', 60)."\n\n";
        $report .= 'Generado: '.now()->format('Y-m-d H:i:s')."\n";
        $report .= str_repeat('-', 60)."\n\n";

        foreach ($data as $section => $content) {
            $sectionTitle = ucfirst(str_replace('_', ' ', $section));
            $report .= "## {$sectionTitle}\n";
            $report .= str_repeat('-', 40)."\n";

            if (is_array($content)) {
                foreach ($content as $key => $value) {
                    $item = is_numeric($key) ? $value : "{$key}: {$value}";
                    $report .= "  • {$item}\n";
                }
            } else {
                $report .= "{$content}\n";
            }

            $report .= "\n";
        }

        return $report;
    }

    private function generateMarkdownReport(string $title, array $data): string
    {
        $report = "# {$title}\n\n";
        $report .= '_Generado: '.now()->format('Y-m-d H:i:s')."_\n\n";
        $report .= "---\n\n";

        foreach ($data as $section => $content) {
            $sectionTitle = ucfirst(str_replace('_', ' ', $section));
            $report .= "## {$sectionTitle}\n\n";

            if (is_array($content)) {
                foreach ($content as $key => $value) {
                    if (is_numeric($key)) {
                        $report .= "- {$value}\n";
                    } else {
                        $report .= "- **{$key}**: {$value}\n";
                    }
                }
            } else {
                $report .= "{$content}\n";
            }

            $report .= "\n";
        }

        return $report;
    }

    private function generateHtmlReport(string $title, array $data): string
    {
        $report = "<!DOCTYPE html>\n<html>\n<head>\n";
        $report .= "<title>{$title}</title>\n";
        $report .= "<style>body{font-family:Arial,sans-serif;margin:20px;}h1{color:#333;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#4CAF50;color:white;}</style>\n";
        $report .= "</head>\n<body>\n";
        $report .= "<h1>{$title}</h1>\n";
        $report .= '<p><em>Generado: '.now()->format('Y-m-d H:i:s')."</em></p>\n";
        $report .= "<table>\n<tr><th>Campo</th><th>Valor</th></tr>\n";

        foreach ($data as $key => $value) {
            $label = ucfirst(str_replace('_', ' ', $key));
            $valueStr = is_array($value) ? json_encode($value) : htmlspecialchars($value);
            $report .= "<tr><td>{$label}</td><td>{$valueStr}</td></tr>\n";
        }

        $report .= "</table>\n</body>\n</html>";

        return $report;
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template' => $schema->string()
                ->description('Tipo de plantilla: summary, detailed, markdown, html')
                ->enum(['summary', 'detailed', 'markdown', 'html'])
                ->required(),
            'data' => $schema->object()
                ->description('Datos para llenar la plantilla')
                ->required(),
            'title' => $schema->string()
                ->description('Título del reporte')
                ->default('Reporte Generado'),
        ];
    }
}
