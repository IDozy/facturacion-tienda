<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with(['empresa', 'roles']);

        // Filtros
        if ($request->has('activo')) {
            $query->where('activo', $request->activo);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('numero_documento', 'like', "%{$search}%");
            });
        }

        if ($request->has('role')) {
            $query->role($request->role);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->where(function ($query) {
                    return $query->where('empresa_id', Auth::user()->empresa_id);
                })
            ],
            'password' => 'required|string|min:8|confirmed',
            'tipo_documento' => 'required|in:DNI,CE,RUC,PASAPORTE',
            'numero_documento' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users')->where(function ($query) {
                    return $query->where('empresa_id', Auth::user()->empresa_id);
                })
            ],
            'telefono' => 'nullable|string|max:20',
            'activo' => 'boolean',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'nombre' => $request->nombre,
                'email' => $request->email,
                'password' => $request->password,
                'tipo_documento' => $request->tipo_documento,
                'numero_documento' => $request->numero_documento,
                'telefono' => $request->telefono,
                'empresa_id' => Auth::user()->empresa_id,
                'activo' => $request->get('activo', true),
            ]);

            // Asignar roles si se proporcionan
            if ($request->has('roles')) {
                $user->syncRoles($request->roles);
            }

            return response()->json([
                'message' => 'Usuario creado exitosamente',
                'data' => $user->load(['empresa', 'roles'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        // Verificar que el usuario pertenece a la misma empresa
        if ($user->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        return response()->json([
            'data' => $user->load(['empresa', 'roles', 'permissions'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        // Verificar que el usuario pertenece a la misma empresa
        if ($user->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)->where(function ($query) {
                    return $query->where('empresa_id', Auth::user()->empresa_id);
                })
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'tipo_documento' => 'sometimes|required|in:DNI,CE,RUC,PASAPORTE',
            'numero_documento' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('users')->ignore($user->id)->where(function ($query) {
                    return $query->where('empresa_id', Auth::user()->empresa_id);
                })
            ],
            'telefono' => 'nullable|string|max:20',
            'activo' => 'boolean',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->only([
                'nombre',
                'email',
                'tipo_documento',
                'numero_documento',
                'telefono',
                'activo'
            ]);

            // Solo actualizar password si se proporciona
            if ($request->filled('password')) {
                $data['password'] = $request->password;
            }

            $user->update($data);

            // Actualizar roles si se proporcionan
            if ($request->has('roles')) {
                $user->syncRoles($request->roles);
            }

            return response()->json([
                'message' => 'Usuario actualizado exitosamente',
                'data' => $user->load(['empresa', 'roles'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Verificar que el usuario pertenece a la misma empresa
        if ($user->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        // No permitir eliminar el propio usuario
        if ($user->id === Auth::id()) {
            return response()->json([
                'message' => 'No puedes eliminar tu propio usuario'
            ], 403);
        }

        try {
            $user->delete();

            return response()->json([
                'message' => 'Usuario eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar/Desactivar usuario
     */
    public function toggleStatus(User $user)
    {
        // Verificar que el usuario pertenece a la misma empresa
        if ($user->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        // No permitir desactivar el propio usuario
        if ($user->id === Auth::id()) {
            return response()->json([
                'message' => 'No puedes desactivar tu propio usuario'
            ], 403);
        }

        try {
            $user->activo = !$user->activo;
            $user->save();

            return response()->json([
                'message' => $user->activo ? 'Usuario activado' : 'Usuario desactivado',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cambiar el estado del usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener perfil del usuario autenticado
     */

    public function profile()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $user->load(['empresa', 'roles', 'permissions']);

        return response()->json([
            'data' => $user
        ]);
    }


    /**
     * Actualizar perfil del usuario autenticado
     */
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */ 
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)->where(function ($query) use ($user) {
                    return $query->where('empresa_id', $user->empresa_id);
                })
            ],
            'telefono' => 'nullable|string|max:20',
            'password_actual' => 'required_with:password|string',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verificar password actual si se va a cambiar
            if ($request->filled('password')) {
                if (!Hash::check($request->password_actual, $user->password)) {
                    return response()->json([
                        'message' => 'La contraseña actual es incorrecta',
                        'errors' => [
                            'password_actual' => ['La contraseña actual es incorrecta']
                        ]
                    ], 422);
                }
            }

            $data = $request->only(['nombre', 'email', 'telefono']);

            if ($request->filled('password')) {
                $data['password'] = $request->password;
            }

            $user->update($data);

            return response()->json([
                'message' => 'Perfil actualizado exitosamente',
                'data' => $user->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el perfil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asignar roles a un usuario
     */
    public function assignRoles(Request $request, User $user)
    {
        // Verificar que el usuario pertenece a la misma empresa
        if ($user->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user->syncRoles($request->roles);

            return response()->json([
                'message' => 'Roles asignados exitosamente',
                'data' => $user->load('roles')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al asignar roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asignar permisos directos a un usuario
     */
    public function assignPermissions(Request $request, User $user)
    {
        // Verificar que el usuario pertenece a la misma empresa
        if ($user->empresa_id !== Auth::user()->empresa_id) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user->syncPermissions($request->permissions);

            return response()->json([
                'message' => 'Permisos asignados exitosamente',
                'data' => $user->load('permissions')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al asignar permisos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
