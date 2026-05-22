<?php

use App\Http\Middleware\SetLocale;
use App\Http\Middleware\IPWhitelist;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(append: [SetLocale::class]);
        $middleware->alias(['ip.whitelist' => IPWhitelist::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
                return response()->json([
                    'success' => false,
                    'message' => 'error.unauthenticated',
                    'errors' => [],
                ], 401);
            }

            return null;
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
                return response()->json([
                    'success' => false,
                    'message' => 'error.forbidden',
                    'errors' => [],
                ], 403);
            }

            return null;
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request) {
            if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
                return response()->json([
                    'success' => false,
                    'message' => 'error.forbidden',
                    'errors' => [],
                ], 403);
            }

            return null;
        });
    })->create();
