<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }

            return redirect()->route('login');
        }

        // Si el usuario es administrador, tiene acceso irrestricto
        if ($user->isAdmin()) {
            return $next($request);
        }

        if (empty($roles)) {
            return $next($request);
        }

        $userRole = strtolower($user->role ?? 'user');
        $allowedRoles = array_map('strtolower', $roles);

        if (! in_array($userRole, $allowedRoles, true)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Acceso denegado: permisos insuficientes para esta operación.',
                ], 403);
            }

            abort(403, 'Acceso denegado.');
        }

        return $next($request);
    }
}
