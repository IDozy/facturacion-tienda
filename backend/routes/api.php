<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmpresaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\SerieController;
use App\Http\Controllers\Api\ComprobanteController;

// Ruta de prueba
Route::get('/test', function () {
    return response()->json([
        'message' => 'API funcionando correctamente',
        'timestamp' => now()
    ]);
});


// Rutas de autenticación (públicas)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    
    // Rutas protegidas
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});


Route::middleware('auth:sanctum')->group(function () {
    

// Empresa
Route::prefix('empresa')->group(function () {
    Route::get('/', [EmpresaController::class, 'index']);
    Route::put('/', [EmpresaController::class, 'update']);
});

// Clientes
Route::prefix('clientes')->group(function () {
    Route::get('/', [ClienteController::class, 'index']);
    Route::post('/', [ClienteController::class, 'store']);
    Route::get('/buscar', [ClienteController::class, 'buscar']);
    Route::get('/{id}', [ClienteController::class, 'show']);
    Route::put('/{id}', [ClienteController::class, 'update']);
    Route::patch('/{id}', [ClienteController::class, 'update']);
    Route::delete('/{id}', [ClienteController::class, 'destroy']);
});

// Productos
Route::prefix('productos')->group(function () {
    Route::get('/', [ProductoController::class, 'index']);
    Route::post('/', [ProductoController::class, 'store']);
    Route::get('/buscar', [ProductoController::class, 'buscar']);
    Route::get('/{id}', [ProductoController::class, 'show']);
    Route::put('/{id}', [ProductoController::class, 'update']);
    Route::patch('/{id}', [ProductoController::class, 'update']);
    Route::delete('/{id}', [ProductoController::class, 'destroy']);
});

// Series
Route::prefix('series')->group(function () {
    Route::get('/', [SerieController::class, 'index']);
    Route::get('/tipo/{tipo}', [SerieController::class, 'porTipo']);
    Route::get('/{id}', [SerieController::class, 'show']);
});

// Comprobantes
Route::prefix('comprobantes')->group(function () {
    Route::get('/', [ComprobanteController::class, 'index']);
    Route::post('/', [ComprobanteController::class, 'store']);
    Route::get('/{id}', [ComprobanteController::class, 'show']);
    Route::post('/{id}/generar-xml', [ComprobanteController::class, 'generarXML']);
    Route::get('/{id}/xml', [ComprobanteController::class, 'verXML']);
    Route::post('/{id}/anular', [ComprobanteController::class, 'anular']);
    Route::post('/{id}/enviar-sunat', [ComprobanteController::class, 'enviarSunat']);
});

});