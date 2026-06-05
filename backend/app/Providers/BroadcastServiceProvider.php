<?php

namespace HiEvents\Providers;

use HiEvents\Http\Middleware\EncryptCookies;
use HiEvents\Http\Middleware\SetAccountContext;
use HiEvents\Http\Middleware\SetJwtFromCookie;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * Real-time auth uses the same JWT cookie as the REST API (see SetJwtFromCookie).
     */
    public function boot(): void
    {
        Broadcast::routes([
            'prefix' => 'api',
            'middleware' => [
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                SetJwtFromCookie::class,
                'auth:api',
                SetAccountContext::class,
            ],
        ]);

        require base_path('routes/channels.php');
    }
}
