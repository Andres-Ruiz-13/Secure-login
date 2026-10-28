<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and apply OWASP security headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Generar un Nonce criptográficamente seguro único por petición
        $nonce = base64_encode(random_bytes(16));

        // Compartir el nonce con las vistas Blade y Vite
        View::share('cspNonce', $nonce);
        if (class_exists(Vite::class)) {
            Vite::useCspNonce($nonce);
        }

        /** @var Response $response */
        $response = $next($request);

        // 2. Eliminar completamente la cabecera X-Powered-By a nivel PHP y HTTP Response
        header_remove('X-Powered-By');
        $response->headers->remove('X-Powered-By');

        // 3. Mitigación de "Gran Redirección detectada":
        // Si el estado es 301/302/303/307/308, vaciamos el cuerpo HTML para evitar
        // fugas de información sensible o tokens y reducir Content-Length a 0.
        if ($response->isRedirection()) {
            $response->setContent('');
        }

        // 4. Cabeceras de defensa en profundidad obligatorias
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), camera=(), microphone=(), payment=()');

        // 5. Política CSP estricta (Sin directivas wildcard ni unsafe-eval/unsafe-inline en scripts)
        $viteDevSources = '';
        if (app()->environment('local')) {
            $viteDevSources = ' http://127.0.0.1:5173 http://localhost:5173';
        }

        $csp = "default-src 'self'; " .
               "base-uri 'self'; " .
               "object-src 'none'; " .
               "frame-ancestors 'self'; " .
               "form-action 'self'; " .
               "script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net{$viteDevSources}; " .
               "style-src 'self' 'nonce-{$nonce}' https://fonts.googleapis.com https://cdn.jsdelivr.net{$viteDevSources}; " .
               "style-src-attr 'self' 'unsafe-inline'; " .
               "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:; " .
               "img-src 'self' data: blob: https://placehold.co; " .
               "connect-src 'self' https://cdn.jsdelivr.net{$viteDevSources}; " .
               "frame-src 'self' https://www.youtube.com https://player.vimeo.com;";

        $response->headers->set('Content-Security-Policy', $csp);

        if (app()->environment('production') || $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
