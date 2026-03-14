<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user() || !$request->user()->rol) {
             return response()->json(['message' => 'Sin permisos de acceso'], 403);
        }

        // Verificar si el rol del usuario está dentro de los permitidos
        if (!in_array($request->user()->rol->nombre, $roles)) {
            return response()->json([
                'message' => 'Acceso denegado: Tu rol (' . $request->user()->rol->nombre . ') no tiene permiso para esta acción.'
            ], 403);
        }

        return $next($request);
    }
}
