<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // 1️⃣ Usuario no autenticado
        if (!$user) {
            return response()->json([
                'message' => 'No autenticado. Debes iniciar sesión.'
            ], 401);
        }

        // 2️⃣ Usuario sin rol asignado
        if (!$user->rol_id) {
            return response()->json([
                'message' => 'Usuario sin rol asignado. Contacta al administrador.'
            ], 403);
        }

        // 3️⃣ Usuario con rol cargado incorrectamente o inexistente
        if (!$user->rol) {
            return response()->json([
                'message' => 'El rol asignado al usuario no existe en el sistema.'
            ], 403);
        }

        // 4️⃣ Usuario no es Administrador
        if ($user->rol->nombre !== 'Administrador') {
            return response()->json([
                'message' => 'Acceso denegado. Se requiere rol Administrador.'
            ], 403);
        }

        // ✅ Todo correcto, continuar con la petición
        return $next($request);
    }
}
