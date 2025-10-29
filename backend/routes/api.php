<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Usuarios\UsuarioController;
use App\Http\Controllers\Api\EmpresaController;
use App\Http\Controllers\Api\Clientes\ClienteController;
use App\Http\Controllers\Api\Inventario\ProductoController;
use App\Http\Controllers\Api\SerieController;
use App\Http\Controllers\Api\Facturacion\ComprobanteController;
use App\Http\Controllers\Api\Inventario\CategoriaController;
use App\Http\Controllers\Api\Contabilidad\AsientoController;
use App\Http\Controllers\Api\Usuarios\RolController;
use App\Http\Controllers\Api\Usuarios\PermisoController;

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
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
    });
});

// Rutas protegidas con autenticación
Route::middleware('auth:sanctum')->group(function () {

    // ===== PERFIL DEL USUARIO AUTENTICADO =====
    Route::prefix('perfil')->group(function () {
        Route::get('/', [UsuarioController::class, 'perfil']);
        Route::put('/', [UsuarioController::class, 'actualizarPerfil']);
        Route::post('/cambiar-password', [UsuarioController::class, 'cambiarPassword']);
    });

    // ===== GESTIÓN DE USUARIOS (Solo Admin) =====
    Route::middleware('admin')->prefix('usuarios')->group(function () {
        Route::get('/', [UsuarioController::class, 'index']);
        Route::post('/', [UsuarioController::class, 'store']);
        Route::get('/{usuarioId}', [UsuarioController::class, 'show']);
        Route::put('/{usuarioId}', [UsuarioController::class, 'update']);
        Route::delete('/{usuarioId}', [UsuarioController::class, 'destroy']);
        Route::post('/{usuarioId}/cambiar-password', [UsuarioController::class, 'cambiarPassword']);
    });

    // ===== ROLES (Solo Admin) =====
    Route::middleware('admin')->prefix('roles')->group(function () {
        Route::get('/', [RolController::class, 'index']);
        Route::post('/', [RolController::class, 'store']);
        Route::get('/{rolId}', [RolController::class, 'show']);
        Route::put('/{rolId}', [RolController::class, 'update']);
        Route::delete('/{rolId}', [RolController::class, 'destroy']);
        Route::post('/{rolId}/asignar-permisos', [RolController::class, 'asignarPermisos']);
    });

    // ===== PERMISOS (Solo Admin) =====
    Route::middleware('admin')->prefix('permisos')->group(function () {
        Route::get('/', [PermisoController::class, 'index']);
        Route::post('/', [PermisoController::class, 'store']);
        Route::get('/modulo/{modulo}', [PermisoController::class, 'porModulo']);
    });

    // ===== EMPRESA =====
    Route::prefix('empresa')->group(function () {
        Route::get('/', [EmpresaController::class, 'index']);
        Route::put('/', [EmpresaController::class, 'update']);
        Route::post('/logo', [EmpresaController::class, 'uploadLogo']);
    });

    // ===== CATEGORÍAS DE PRODUCTOS =====
    Route::prefix('categorias')->group(function () {
        Route::get('/', [CategoriaController::class, 'index']);
        Route::post('/', [CategoriaController::class, 'store']);
        Route::get('/{categoriaId}', [CategoriaController::class, 'show']);
        Route::put('/{categoriaId}', [CategoriaController::class, 'update']);
        Route::delete('/{categoriaId}', [CategoriaController::class, 'destroy']);
    });

    // ===== CLIENTES =====
    Route::prefix('clientes')->group(function () {
        Route::get('/', [ClienteController::class, 'index']);
        Route::post('/', [ClienteController::class, 'store']);
        Route::get('/buscar', [ClienteController::class, 'buscar']);
        Route::get('/exportar', [ClienteController::class, 'exportar']);
        Route::get('/{clienteId}', [ClienteController::class, 'show']);
        Route::put('/{clienteId}', [ClienteController::class, 'update']);
        Route::patch('/{clienteId}', [ClienteController::class, 'update']);
        Route::delete('/{clienteId}', [ClienteController::class, 'destroy']);
        Route::get('/{clienteId}/compras', [ClienteController::class, 'compras']);
    });

    // ===== PRODUCTOS =====
    Route::prefix('productos')->group(function () {
        Route::get('/', [ProductoController::class, 'index']);
        Route::post('/', [ProductoController::class, 'store']);
        Route::get('/buscar', [ProductoController::class, 'buscar']);
        Route::get('/bajo-stock', [ProductoController::class, 'bajoStock']);
        Route::get('/exportar', [ProductoController::class, 'exportar']);
        Route::get('/{productoId}', [ProductoController::class, 'show']);
        Route::put('/{productoId}', [ProductoController::class, 'update']);
        Route::patch('/{productoId}', [ProductoController::class, 'update']);
        Route::delete('/{productoId}', [ProductoController::class, 'destroy']);
        Route::post('/{productoId}/imagen', [ProductoController::class, 'uploadImagen']);
    });

    // ===== SERIES =====
    Route::prefix('series')->group(function () {
        Route::get('/', [SerieController::class, 'index']);
        Route::post('/', [SerieController::class, 'store']);
        Route::get('/tipo/{tipo}', [SerieController::class, 'porTipo']);
        Route::get('/{serieId}', [SerieController::class, 'show']);
        Route::put('/{serieId}', [SerieController::class, 'update']);
        Route::delete('/{serieId}', [SerieController::class, 'destroy']);
    });

    // ===== COMPROBANTES =====
    Route::prefix('comprobantes')->group(function () {
        Route::get('/', [ComprobanteController::class, 'index']);
        Route::post('/', [ComprobanteController::class, 'store']);
        Route::get('/exportar', [ComprobanteController::class, 'exportar']);
        Route::get('/{comprobanteId}', [ComprobanteController::class, 'show']);
        Route::post('/{comprobanteId}/generar-xml', [ComprobanteController::class, 'generarXML']);
        Route::get('/{comprobanteId}/xml', [ComprobanteController::class, 'verXML']);
        Route::get('/{comprobanteId}/pdf', [ComprobanteController::class, 'generarPDF']);
        Route::post('/{comprobanteId}/anular', [ComprobanteController::class, 'anular']);
        Route::post('/{comprobanteId}/enviar-sunat', [ComprobanteController::class, 'enviarSunat']);
        Route::get('/{comprobanteId}/consultar-sunat', [ComprobanteController::class, 'consultarSunat']);
        Route::post('/{comprobanteId}/enviar-email', [ComprobanteController::class, 'enviarEmail']);
    });

    // ===== ASIENTOS CONTABLES =====
    Route::prefix('asientos')->group(function () {
        Route::get('/', [AsientoController::class, 'index']);
        Route::post('/', [AsientoController::class, 'store']);
        Route::get('/{asientoId}', [AsientoController::class, 'show']);
        Route::put('/{asientoId}', [AsientoController::class, 'update']);
        Route::delete('/{asientoId}', [AsientoController::class, 'destroy']);
        Route::post('/{asientoId}/registrar', [AsientoController::class, 'registrar']);
        Route::post('/{asientoId}/anular', [AsientoController::class, 'anular']);
    });

});

// Ruta para manejar rutas no encontradas
Route::fallback(function () {
    return response()->json([
        'message' => 'Ruta no encontrada',
        'status' => 404
    ], 404);
});