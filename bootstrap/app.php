<?php

use App\Actions\LogRepeatedHttpError;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware(['web', 'auth'])
                ->prefix('roles')
                ->name('roles.')
                ->group(base_path('routes/roles.php'));

            Route::middleware(['web', 'auth'])
                ->prefix('permissions')
                ->name('permissions.')
                ->group(base_path('routes/permissions.php'));

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
                ->prefix('issues')
                ->name('issues.')
                ->group(base_path('routes/issues.php'));

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
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            app(LogRepeatedHttpError::class)->handle($request, $response->getStatusCode());

            if ($response->getStatusCode() === 403
                && ! $request->expectsJson()
                && ! $request->hasHeader('X-Livewire')
                && $request->hasSession()) {
                $previous = url()->previous();
                $origin = $request->getSchemeAndHttpHost();

                if (! str_starts_with($previous, $origin . '/')
                    || strtok($previous, '?') === $request->url()) {
                    $previous = route('home');
                }

                return redirect()->to($previous, 303)->with('access_denied', true);
            }

            return $response;
        });

        $exceptions->report(function (Throwable $exception): void {
            try {
                activity('exceptions')
                    ->event('exception_reported')
                    ->withProperties([
                        'exception' => $exception::class,
                        'code' => $exception->getCode(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                    ])
                    ->log($exception->getMessage());
            } catch (Throwable $loggingException) {
                // Do not recursively report a failed activity write or replace the original exception.
                logger()->error('Could not write exception to activity log.', [
                    'exception' => $loggingException,
                ]);
            }
        });
    })->create();
