<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersAndHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present_on_responses(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        // 1. Verificación de ausencia de X-Powered-By
        $this->assertFalse($response->headers->has('X-Powered-By'), 'X-Powered-By header should not be present');

        // 2. Cabeceras de seguridad requeridas por OWASP
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertTrue($response->headers->has('Permissions-Policy'));

        // 3. Verificación de Content-Security-Policy estricta
        $this->assertTrue($response->headers->has('Content-Security-Policy'));
        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("script-src 'self' 'nonce-", $csp);
        $this->assertStringNotContainsString("script-src *", $csp);
        $this->assertStringNotContainsString("'unsafe-eval'", $csp);
        $this->assertStringNotContainsString("script-src 'self' 'nonce-.*'unsafe-inline'", $csp);
    }

    public function test_big_redirect_mitigation_clears_response_body(): void
    {
        // Petición no autenticada a ruta protegida genera redirección a /login
        $response = $this->get('/dashboard');

        $response->assertStatus(302);
        $response->assertRedirect('/login');

        // El cuerpo debe estar vacío para silenciar la alerta de OWASP ZAP "Gran redirección detectada"
        $this->assertSame('', $response->getContent(), 'Redirection response body must be empty');
    }

    public function test_cors_configuration_restricts_arbitrary_origins(): void
    {
        // Petición OPTIONS o GET desde un origen no permitido
        $response = $this->withHeaders([
            'Origin' => 'https://malicious-site.com',
            'Access-Control-Request-Method' => 'GET',
        ])->getJson('/api/stock_bajo');

        // No debe otorgar Access-Control-Allow-Origin a sitios desconocidos
        $allowOrigin = $response->headers->get('Access-Control-Allow-Origin');
        $this->assertNotSame('*', $allowOrigin);
        $this->assertNotSame('https://malicious-site.com', $allowOrigin);
    }

    public function test_session_cookie_security_settings(): void
    {
        $this->assertTrue(config('session.http_only'), 'Session cookie must have HttpOnly enabled');
        $this->assertSame('lax', config('session.same_site'), 'Session cookie same_site must be lax');
    }
}

