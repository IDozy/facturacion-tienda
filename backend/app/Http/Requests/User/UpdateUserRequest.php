<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? null;

        return [
            'nombre' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId)->where(function ($query) {
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
                Rule::unique('users')->ignore($userId)->where(function ($query) {
                    return $query->where('empresa_id', Auth::user()->empresa_id);
                })
            ],
            'telefono' => 'nullable|string|max:20',
            'activo' => 'boolean',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
        ];
    }
}

