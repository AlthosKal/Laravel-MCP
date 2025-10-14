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
