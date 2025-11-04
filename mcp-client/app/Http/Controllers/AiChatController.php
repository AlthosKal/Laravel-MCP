<?php

namespace App\Http\Controllers;

use App\Services\AiAssistantService;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controlador para el Chat con IA.
 *
 * Responsabilidad única: Manejar las peticiones HTTP del chat.
 * La lógica de negocio está delegada al AiAssistantService (SRP).
 */
class AiChatController extends Controller
{
    public function __construct(
        private readonly AiAssistantService $aiAssistant,
        private readonly DocumentService $documentService
    ) {}

    /**
     * Muestra la vista principal del chat.
     */
    public function index()
    {
        return view('chat.index');
    }

    /**
     * Procesa un mensaje del usuario (sin streaming).
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $response = $this->aiAssistant->processMessage($validated['message']);

        return response()->json($response);
    }

    /**
     * Procesa un mensaje del usuario con streaming (SSE - Server-Sent Events).
     */
    public function sendMessageStreaming(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        return response()->stream(
            function () use ($validated) {
                // Iniciar buffer de salida
                ob_start();

                // Configurar headers para SSE
                echo 'data: '.json_encode(['type' => 'start'])."\n\n";
                ob_flush();
                flush();

                $this->aiAssistant->processMessageStreaming(
                    $validated['message'],
                    function (array $data, bool $done) {
                        echo 'data: '.json_encode($data)."\n\n";
                        ob_flush();
                        flush();

                        if ($done) {
                            echo 'data: '.json_encode(['type' => 'end'])."\n\n";
                            ob_flush();
                            flush();
                        }
                    }
                );

                // Finalizar y limpiar el buffer
                ob_end_flush();
            },
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }

    /**
     * Lista las herramientas MCP disponibles.
     */
    public function listTools(): JsonResponse
    {
        $tools = $this->aiAssistant->getAvailableTools();

        return response()->json([
            'tools' => $tools,
            'count' => count($tools),
        ]);
    }

    /**
     * Sube un nuevo documento al sistema RAG.
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:40',
            'content' => 'required|string',
            'metadata' => 'nullable|array',
        ]);

        $result = $this->documentService->uploadDocument(
            $validated['title'],
            $validated['content'],
            $validated['metadata'] ?? []
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Lista las versiones de un documento.
     */
    public function listDocumentVersions(string $title): JsonResponse
    {
        $result = $this->documentService->listVersions($title);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Elimina un documento.
     */
    public function deleteDocument(string $documentId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'soft_delete' => 'nullable|boolean',
        ]);

        $result = $this->documentService->deleteDocument(
            $documentId,
            $validated['soft_delete'] ?? true
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Verifica la conectividad con el servidor RAG.
     */
    public function checkRagStatus(): JsonResponse
    {
        $isAvailable = $this->documentService->checkConnection();

        return response()->json([
            'status' => $isAvailable ? 'available' : 'unavailable',
            'connected' => $isAvailable,
        ]);
    }
}
