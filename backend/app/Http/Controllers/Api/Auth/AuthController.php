<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])
            ->where('activo', true)
            ->with('rol')
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        // Crear token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
            'message' => 'Login exitoso'
        ]);
    }

    /**
     * Logout
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        auth()->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

    /**
     * Obtener usuario autenticado
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('rol');

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Actualizar perfil
     * PUT /api/auth/perfil
     */
    public function actualizarPerfil(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'telefono' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Perfil actualizado correctamente'
        ]);
    }

    /**
     * Cambiar contraseña
     * POST /api/auth/cambiar-password
     */
    public function cambiarPassword(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'password_actual' => 'required|string',
            'password_nueva' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['password_actual'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Contraseña actual incorrecta'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password_nueva']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente'
        ]);
    }

    /**
     * Registrar usuario (solo admin)
     * POST /api/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'rol_id' => 'required|exists:roles,id',
            'numero_documento' => 'nullable|string|unique:users,numero_documento',
            'tipo_documento' => 'nullable|in:1,4,6,7',
            'telefono' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'nombre' => $validated['nombre'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'rol_id' => $validated['rol_id'],
            'numero_documento' => $validated['numero_documento'] ?? null,
            'tipo_documento' => $validated['tipo_documento'] ?? null,
            'telefono' => $validated['telefono'] ?? null,
            'activo' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $user->load('rol'),
            'message' => 'Usuario registrado correctamente'
        ], 201);
    }

    /**
     * Refrescar token
     * POST /api/auth/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        // Eliminar token actual
        $user->currentAccessToken()->delete();
        
        // Crear nuevo token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
            ],
            'message' => 'Token refrescado correctamente'
        ]);
    }
}