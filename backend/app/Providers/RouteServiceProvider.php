<?php

namespace HiEvents\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(config('app.api_rate_limit_per_minute'))
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('self-service-email', function (Request $request) {
            return Limit::perHour(20)->by($request->route('order_short_id') ?? $request->ip());
        });

        RateLimiter::for('self-service-edit', function (Request $request) {
            return Limit::perHour(20)->by($request->route('order_short_id') ?? $request->ip());
        });

        RateLimiter::for('public-read', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('public-order', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('auth-login', function (Request $request) {
            return Limit::perMinutes(
                config('security.auth.login_decay_minutes', 15),
                config('security.auth.login_max_attempts', 5),
            )->by($request->ip());
        });

        RateLimiter::for('auth-register', function (Request $request) {
            return Limit::perMinutes(
                config('security.auth.register_decay_minutes', 60),
                config('security.auth.register_max_attempts', 3),
            )->by($request->ip());
        });

        RateLimiter::for('auth-password-reset', function (Request $request) {
            return Limit::perMinutes(
                config('security.auth.password_reset_decay_minutes', 60),
                config('security.auth.password_reset_max_attempts', 3),
            )->by($request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
