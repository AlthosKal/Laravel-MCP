<?php

use App\Http\Controllers\AiChatController;
use Illuminate\Support\Facades\Route;

// Página principal del chat
Route::get('/', [AiChatController::class, 'index'])->name('chat.index');

// API para el chat
Route::prefix('api/chat')->group(function () {
    Route::post('/message', [AiChatController::class, 'sendMessage'])->name('chat.send');
    Route::post('/message/stream', [AiChatController::class, 'sendMessageStreaming'])->name('chat.send.stream');
    Route::get('/tools', [AiChatController::class, 'listTools'])->name('chat.tools');
});

// API para gestión de documentos RAG
Route::prefix('api/documents')->group(function () {
    Route::post('/upload', [AiChatController::class, 'uploadDocument'])->name('documents.upload');
    Route::get('/{title}/versions', [AiChatController::class, 'listDocumentVersions'])->name('documents.versions');
    Route::delete('/{documentId}', [AiChatController::class, 'deleteDocument'])->name('documents.delete');
    Route::get('/status', [AiChatController::class, 'checkRagStatus'])->name('documents.status');
});
