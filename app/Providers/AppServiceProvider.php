<?php

namespace App\Providers;

use App\Constants\RateLimiterNames;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use Spatie\Activitylog\Facades\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Activity::beforeLogging(function (ActivityContract $activity) {
            if (! app()->runningInConsole()) {
                $activity->properties = $activity->properties->merge([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                ]);
            }
        });

        RateLimiter::for(RateLimiterNames::PUBLIC_QUOTE_SUBMISSION, function (Request $request, string $email): array {
            return [
                Limit::perMinute(6)
                    ->by('ip:' . hash('sha256', $request->ip() ?? '')),
                Limit::perMinute(3)
                    ->by('email:' . hash('sha256', mb_strtolower(mb_trim($email)))),
            ];
        });
    }
}
