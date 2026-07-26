<?php

use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'theme', 'sidebar_state']);

        // Fait confiance à tous les proxies pour lire X-Forwarded-Proto/-For —
        // à restreindre à l'IP du reverse proxy connu si celle-ci est fixe en
        // déploiement (Traefik/Nginx Proxy Manager en homelab, IP variable).
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);

        $middleware->web(prepend: [
            ForceHttps::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SecurityHeaders::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('tasks:notify-due-soon')->dailyAt('08:00');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
