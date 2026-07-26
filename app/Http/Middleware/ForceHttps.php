<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirige vers HTTPS en production. S'appuie sur $request->secure(), donc sur
 * la configuration des proxies de confiance (bootstrap/app.php) pour lire
 * correctement l'en-tête X-Forwarded-Proto derrière un reverse proxy
 * (Traefik, Nginx Proxy Manager...).
 */
class ForceHttps
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production') && ! $request->secure()) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}
