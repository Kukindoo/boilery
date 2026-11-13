<?php

namespace App\Providers;

use App\Constants\RateLimiterNames;
use App\Policies\ActivityLogPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use Spatie\Activitylog\Facades\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
        $this->registerPolicies();

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

    private function registerPolicies(): void
    {
        Gate::policy(Activity::class, ActivityLogPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
    }
}
