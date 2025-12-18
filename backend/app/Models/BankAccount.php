<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'banco',
        'numero',
        'moneda',
        'es_principal',
        'maneja_cheques',
        'updated_by',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
        'maneja_cheques' => 'boolean',
    ];
}
