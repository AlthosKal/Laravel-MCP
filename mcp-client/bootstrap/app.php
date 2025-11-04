<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Manejador global para todas las excepciones
        $exceptions->render(function (\Throwable $e, $request) {
            // Log automático de todas las excepciones
            logger()->error($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Determinar código de estado HTTP
            $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                $statusCode = $e->getStatusCode();
            } elseif (method_exists($e, 'getCode') && $e->getCode() >= 400 && $e->getCode() < 600) {
                $statusCode = $e->getCode();
            }

            // Respuesta JSON/HTML según el request
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'exception' => config('app.debug') ? get_class($e) : null,
                    'trace' => config('app.debug') ? $e->getTraceAsString() : null,
                ], $statusCode);
            }

            // Para vistas web, podemos mostrar una página de error
            return response()->view('errors.default', [
                'message' => $e->getMessage(),
                'exception' => config('app.debug') ? $e : null,
            ], $statusCode);
        });
    })->create();
