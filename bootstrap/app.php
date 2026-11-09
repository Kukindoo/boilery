<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware(['web', 'auth'])
                ->prefix('spare-parts')
                ->name('spare-parts.')
                ->group(base_path('routes/spare-parts.php'));

            Route::middleware(['web', 'auth'])
                ->prefix('files')
                ->name('files.')
                ->group(base_path('routes/files.php'));

            Route::middleware(['web', 'auth'])
                ->prefix('customers')
                ->name('customers.')
                ->group(base_path('routes/customers.php'));

            Route::middleware(['web', 'auth'])
                ->prefix('quotes')
                ->name('quotes.')
                ->group(base_path('routes/quotes.php'));

            Route::middleware(['web', 'auth'])
                ->prefix('works')
                ->name('works.')
                ->group(base_path('routes/works.php'));

            Route::middleware(['web', 'auth'])
                ->prefix('assembly-actions')
                ->name('assembly-actions.')
                ->group(base_path('routes/assembly-actions.php'));

            Route::middleware(['web', 'auth'])
                ->prefix('cold-tests')
                ->name('cold-tests.')
                ->group(base_path('routes/cold-tests.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
