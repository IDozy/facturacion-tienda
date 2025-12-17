<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
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
        ];
    }
}

