<?php

namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class LogRepeatedHttpError
{
    public function handle(Request $request, int $status): void
    {
        if (! in_array($status, [403, 404], true)) {
            return;
        }

        try {
            $user = $request->user();
            $identity = $user !== null
                ? 'user:' . $user->getAuthIdentifier()
                : 'ip:' . $request->ip();
            $key = 'repeated-http-errors:' . $status . ':' . hash('sha256', $identity);
            $window = max(1, (int) config('activitylog.repeated_http_errors.window_seconds', 300));
            $count = RateLimiter::hit($key, $window);

            if ($count < max(1, (int) config('activitylog.repeated_http_errors.threshold', 20))) {
                return;
            }

            $cooldown = max(1, (int) config('activitylog.repeated_http_errors.cooldown_seconds', 300));

            if (! Cache::add($key . ':cooldown', true, $cooldown)) {
                return;
            }

            activity('exceptions')
                ->event('repeated_http_error')
                ->withProperties([
                    'status' => $status,
                    'count' => $count,
                    'window_seconds' => $window,
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                ])
                ->log('Repeated HTTP ' . $status . ' responses');
        } catch (Throwable $loggingException) {
            logger()->error('Could not write repeated HTTP error to activity log.', [
                'exception' => $loggingException,
            ]);
        }
    }
}
