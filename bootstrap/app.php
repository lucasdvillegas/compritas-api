<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // La ruta vive fuera del grupo "web" para poder generar
            // APP_KEY antes de que se resuelva el encrypter.
            Route::get('/deploy', function () {
                @set_time_limit(0);
                ignore_user_abort(true);

                $results = [];

                foreach ([
                    ['config:clear', []],
                    ['view:clear', []],
                    ['key:generate', ['--force' => true]],
                    ['migrate', ['--force' => true]],
                    ['cache:clear', []],
                    ['config:cache', []],
                    ['view:cache', []],
                ] as [$command, $parameters]) {
                    Artisan::call($command, $parameters);
                    $results[$command] = Artisan::output();
                }

                return response()->json([
                    'success' => true,
                    'results' => $results,
                ]);
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render/Railway terminate TLS and forward the original scheme.
        $middleware->trustProxies(at: '*');

        // API-only app: guests must get a 401 instead of a redirect to a missing login route.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
