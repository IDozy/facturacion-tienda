<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        // Verificar si el usuario tiene rol asignado
        if (!$user->rol_id) {
            return response()->json(['message' => 'Usuario sin rol asignado'], 403);
        }

        // Traer el nombre del rol
        $rolNombre = $user->rol->nombre ?? null;

        if ($rolNombre !== 'Administrador') {
            return response()->json(['message' => 'Acceso denegado, se requiere rol Administrador'], 403);
        }

        return $next($request);
    }
}
