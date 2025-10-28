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
//use App\Http\Controllers\Api\DashboardController;
//use App\Http\Controllers\Api\ReporteController;
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
    Route::prefix('usuarios')->middleware('admin')->group(function () {
        Route::get('/', [UsuarioController::class, 'index']);                          // Listar
        Route::post('/', [UsuarioController::class, 'store']);                         // Crear
        Route::get('/{id}', [UsuarioController::class, 'show']);                       // Ver uno
        Route::put('/{id}', [UsuarioController::class, 'update']);                     // Actualizar
        Route::delete('/{id}', [UsuarioController::class, 'destroy']);                 // Eliminar (soft delete)
        Route::post('/{id}/cambiar-password', [UsuarioController::class, 'cambiarPassword']); // Cambiar pass
    });

    // ===== ROLES (Solo Admin) =====
    Route::prefix('roles')->middleware('admin')->group(function () {
        Route::get('/', [RolController::class, 'index']);                      // Listar roles
        Route::post('/', [RolController::class, 'store']);                     // Crear rol
        Route::get('/{id}', [RolController::class, 'show']);                   // Ver rol
        Route::put('/{id}', [RolController::class, 'update']);                 // Actualizar rol
        Route::delete('/{id}', [RolController::class, 'destroy']);             // Eliminar rol
        Route::post('/{id}/asignar-permisos', [RolController::class, 'asignarPermisos']); // Asignar permisos
    });

    // ===== PERMISOS (Solo Admin) =====
    Route::prefix('permisos')->middleware('admin')->group(function () {
        Route::get('/', [PermisoController::class, 'index']);                  // Listar permisos
        Route::post('/', [PermisoController::class, 'store']);                 // Crear permiso
        Route::get('/modulo/{modulo}', [PermisoController::class, 'porModulo']); // Permisos por módulo
    });

    // ===== DASHBOARD - Estadísticas generales =====
   /* Route::prefix('dashboard')->group(function () {
        Route::get('/estadisticas', [DashboardController::class, 'estadisticas']);
        Route::get('/ventas-mes', [DashboardController::class, 'ventasMes']);
        Route::get('/productos-mas-vendidos', [DashboardController::class, 'productosMasVendidos']);
        Route::get('/ultimas-ventas', [DashboardController::class, 'ultimasVentas']);
        Route::get('/clientes-frecuentes', [DashboardController::class, 'clientesFrecuentes']);
        Route::get('/productos-bajo-stock', [DashboardController::class, 'productosBajoStock']);
    });
    */
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
        Route::get('/{id}', [CategoriaController::class, 'show']);
        Route::put('/{id}', [CategoriaController::class, 'update']);
        Route::delete('/{id}', [CategoriaController::class, 'destroy']);
    });

    // ===== CLIENTES =====
    Route::prefix('clientes')->group(function () {
        Route::get('/', [ClienteController::class, 'index']);
        Route::post('/', [ClienteController::class, 'store']);
        Route::get('/buscar', [ClienteController::class, 'buscar']);
        Route::get('/exportar', [ClienteController::class, 'exportar']);
        Route::get('/{id}', [ClienteController::class, 'show']);
        Route::put('/{id}', [ClienteController::class, 'update']);
        Route::patch('/{id}', [ClienteController::class, 'update']);
        Route::delete('/{id}', [ClienteController::class, 'destroy']);
        Route::get('/{id}/compras', [ClienteController::class, 'compras']);
    });

    // ===== PRODUCTOS =====
    Route::prefix('productos')->group(function () {
        Route::get('/', [ProductoController::class, 'index']);
        Route::post('/', [ProductoController::class, 'store']);
        Route::get('/buscar', [ProductoController::class, 'buscar']);
        Route::get('/bajo-stock', [ProductoController::class, 'bajoStock']);
        Route::get('/exportar', [ProductoController::class, 'exportar']);
        Route::get('/{id}', [ProductoController::class, 'show']);
        Route::put('/{id}', [ProductoController::class, 'update']);
        Route::patch('/{id}', [ProductoController::class, 'update']);
        Route::delete('/{id}', [ProductoController::class, 'destroy']);
        Route::post('/{id}/imagen', [ProductoController::class, 'uploadImagen']);
    });

    // ===== SERIES =====
    Route::prefix('series')->group(function () {
        Route::get('/', [SerieController::class, 'index']);
        Route::post('/', [SerieController::class, 'store']);
        Route::get('/tipo/{tipo}', [SerieController::class, 'porTipo']);
        Route::get('/{id}', [SerieController::class, 'show']);
        Route::put('/{id}', [SerieController::class, 'update']);
        Route::delete('/{id}', [SerieController::class, 'destroy']);
    });

    // ===== COMPROBANTES =====
    Route::prefix('comprobantes')->group(function () {
        Route::get('/', [ComprobanteController::class, 'index']);
        Route::post('/', [ComprobanteController::class, 'store']);
        Route::get('/exportar', [ComprobanteController::class, 'exportar']);
        Route::get('/{id}', [ComprobanteController::class, 'show']);
        Route::post('/{id}/generar-xml', [ComprobanteController::class, 'generarXML']);
        Route::get('/{id}/xml', [ComprobanteController::class, 'verXML']);
        Route::get('/{id}/pdf', [ComprobanteController::class, 'generarPDF']);
        Route::post('/{id}/anular', [ComprobanteController::class, 'anular']);
        Route::post('/{id}/enviar-sunat', [ComprobanteController::class, 'enviarSunat']);
        Route::get('/{id}/consultar-sunat', [ComprobanteController::class, 'consultarSunat']);
        Route::post('/{id}/enviar-email', [ComprobanteController::class, 'enviarEmail']);
    });

    // ===== REPORTES =====
    /*
    Route::prefix('reportes')->group(function () {
        Route::get('/ventas', [ReporteController::class, 'ventas']);
        Route::get('/ventas-detallado', [ReporteController::class, 'ventasDetallado']);
        Route::get('/productos', [ReporteController::class, 'productos']);
        Route::get('/clientes', [ReporteController::class, 'clientes']);
        Route::get('/comprobantes', [ReporteController::class, 'comprobantes']);
        Route::get('/inventario', [ReporteController::class, 'inventario']);
        Route::post('/exportar-excel', [ReporteController::class, 'exportarExcel']);
    });
    */

    // ===== ASIENTOS CONTABLES =====
    Route::prefix('asientos')->group(function () {
        Route::get('/', [AsientoController::class, 'index']);
        Route::post('/', [AsientoController::class, 'store']);
        Route::get('/{id}', [AsientoController::class, 'show']);
        Route::put('/{id}', [AsientoController::class, 'update']);
        Route::delete('/{id}', [AsientoController::class, 'destroy']);
        Route::post('/{id}/registrar', [AsientoController::class, 'registrar']);
        Route::post('/{id}/anular', [AsientoController::class, 'anular']);
    });

});

// Ruta para manejar rutas no encontradas
Route::fallback(function () {
    return response()->json([
        'message' => 'Ruta no encontrada',
        'status' => 404
    ], 404);
});