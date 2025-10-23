<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index(): JsonResponse
    {
        $usuarios = User::where('activo', true)
            ->with('rol')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $usuarios,
        ]);
    }

    public function store(Request $request): JsonResponse
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

        $usuario = User::create([
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
            'data' => $usuario->load('rol'),
            'message' => 'Usuario creado correctamente'
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $usuario = User::with('rol')->find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $usuario,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'rol_id' => 'sometimes|exists:roles,id',
            'numero_documento' => 'sometimes|string|unique:users,numero_documento,' . $id,
            'telefono' => 'nullable|string|max:20',
            'activo' => 'sometimes|boolean',
        ]);

        $usuario->update($validated);

        return response()->json([
            'success' => true,
            'data' => $usuario->load('rol'),
            'message' => 'Usuario actualizado correctamente'
        ]);
    }

    public function cambiarPassword(Request $request, string $id): JsonResponse
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $validated = $request->validate([
            'password_actual' => 'required|string',
            'password_nueva' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['password_actual'], $usuario->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Contraseña actual incorrecta'
            ], 422);
        }

        $usuario->update([
            'password' => Hash::make($validated['password_nueva']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente'
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $usuario->update(['activo' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado correctamente'
        ]);
    }

    public function perfil(): JsonResponse
    {
        $usuario = auth()->user()->load('rol');

        return response()->json([
            'success' => true,
            'data' => $usuario,
        ]);
    }

    public function actualizarPerfil(Request $request): JsonResponse
    {
        $usuario = auth()->user();

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $usuario->id,
            'telefono' => 'nullable|string|max:20',
        ]);

        $usuario->update($validated);

        return response()->json([
            'success' => true,
            'data' => $usuario,
            'message' => 'Perfil actualizado correctamente'
        ]);
    }
}