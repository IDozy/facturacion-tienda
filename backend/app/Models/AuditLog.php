<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'user_id',
        'action',
        'module',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
