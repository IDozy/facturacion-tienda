<?php

use App\Http\Controllers\Api\AuditoriaController;
use App\Http\Controllers\Api\CajaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\Compras\CompraController;
use App\Http\Controllers\Api\Compras\CompraDetalleController;
use App\Http\Controllers\Api\Compras\ProveedorController;
use App\Http\Controllers\Api\ConfiguracionController;
use App\Http\Controllers\Api\ConfiguracionEmpresaController;
use App\Http\Controllers\Api\Contabilidad\AsientoController;
use App\Http\Controllers\Api\Contabilidad\AsientoDetalleController;
use App\Http\Controllers\Api\Contabilidad\DiarioController;
use App\Http\Controllers\Api\Contabilidad\PeriodoContableController;
use App\Http\Controllers\Api\Contabilidad\PlanCuentaController;
use App\Http\Controllers\Api\EmpresaController;
use App\Http\Controllers\Api\Facturacion\ComprobanteController;
use App\Http\Controllers\Api\Facturacion\ComprobanteDetalleController;
use App\Http\Controllers\Api\Facturacion\GuiaRemisionController;
use App\Http\Controllers\Api\Facturacion\SerieController;
use App\Http\Controllers\Api\Inventario\AjusteInventarioController;
use App\Http\Controllers\Api\Inventario\AlmacenController;
use App\Http\Controllers\Api\Inventario\AlmacenProductoController;
use App\Http\Controllers\Api\Inventario\CategoriaController;
use App\Http\Controllers\Api\Inventario\MovimientoStockController;
use App\Http\Controllers\Api\Inventario\ProductoController;
use App\Http\Controllers\Api\Inventario\TransferenciaStockController;
use App\Http\Controllers\Api\LibroElectronicoController;
use App\Http\Controllers\Api\MedioPagoController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\RespuestaSunatController;
use App\Http\Controllers\Api\RetencionController;
use App\Http\Controllers\Api\RolController;
use App\Http\Controllers\Api\TablaSunatController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Rutas públicas (sin autenticación)
|--------------------------------------------------------------------------
*/

// LOGIN
Route::post('api/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Credenciales incorrectas'
        ], 401);
    }

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'success' => true,
        'user' => $user,
        'token' => $token,
        'token_type' => 'Bearer'
    ]);
});

/*
|--------------------------------------------------------------------------
| Rutas protegidas con Sanctum
|--------------------------------------------------------------------------
*/

Route::prefix('api')->middleware(['auth:sanctum'])->group(function () {

    // LOGOUT
    Route::post('logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Logout exitoso'
        ]);
    });

    // Usuario autenticado
    Route::get('user', function (Request $request) {
        return response()->json([
            'success' => true,
            'user' => $request->user()
        ]);
    });

    // Perfil de usuario
    Route::get('profile', [UserController::class, 'profile']);
    Route::put('profile', [UserController::class, 'updateProfile']);


    Route::get('/roles', [RolController::class, 'index']);


    // Gestión de usuarios
    Route::apiResource('users', UserController::class);
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
    Route::post('users/{user}/assign-roles', [UserController::class, 'assignRoles']);
    Route::post('users/{user}/assign-permissions', [UserController::class, 'assignPermissions']);

    // Tablas SUNAT
    Route::get('tablas-sunat/tipos', [TablaSunatController::class, 'tipos']);
    Route::get('tablas-sunat/tipo/{tipo}', [TablaSunatController::class, 'porTipo']);
    Route::get('tablas-sunat/buscar-codigo', [TablaSunatController::class, 'buscarPorCodigo']);
    Route::post('tablas-sunat/validar-codigo', [TablaSunatController::class, 'validarCodigo']);
    Route::post('tablas-sunat/importar', [TablaSunatController::class, 'importar']);
    Route::delete('tablas-sunat/clear-cache', [TablaSunatController::class, 'clearCache']);
    Route::patch('tablas-sunat/{tablaSunat}/toggle-status', [TablaSunatController::class, 'toggleStatus']);

    // Catálogos SUNAT
    Route::get('catalogos/tipos-documento', [TablaSunatController::class, 'tiposDocumento']);
    Route::get('catalogos/tipos-afectacion', [TablaSunatController::class, 'tiposAfectacion']);
    Route::get('catalogos/unidades-medida', [TablaSunatController::class, 'unidadesMedida']);
    Route::get('catalogos/tipos-moneda', [TablaSunatController::class, 'tiposMoneda']);
    Route::get('catalogos/tipos-comprobante', [TablaSunatController::class, 'tiposComprobante']);

    Route::apiResource('tablas-sunat', TablaSunatController::class);

    // Retenciones
    Route::apiResource('retenciones', RetencionController::class);
    Route::post('retenciones/{retencion}/aplicar', [RetencionController::class, 'aplicar']);
    Route::post('retenciones/{retencion}/anular', [RetencionController::class, 'anular']);
    Route::post('retenciones/{retencion}/calcular', [RetencionController::class, 'calcular']);
    Route::get('retenciones-estadisticas', [RetencionController::class, 'estadisticas']);

    // Respuestas SUNAT
    Route::apiResource('respuestas-sunat', RespuestaSunatController::class);
    Route::get('respuestas-sunat-para-reintento', [RespuestaSunatController::class, 'paraReintento']);
    Route::post('respuestas-sunat/{respuestaSunat}/programar-reintento', [RespuestaSunatController::class, 'programarReintento']);
    Route::post('respuestas-sunat/{respuestaSunat}/marcar-aceptado', [RespuestaSunatController::class, 'marcarAceptado']);
    Route::post('respuestas-sunat/{respuestaSunat}/marcar-rechazado', [RespuestaSunatController::class, 'marcarRechazado']);
    Route::get('respuestas-sunat/{respuestaSunat}/descargar-cdr', [RespuestaSunatController::class, 'descargarCdr']);
    Route::get('respuestas-sunat/{respuestaSunat}/descargar-xml', [RespuestaSunatController::class, 'descargarXml']);
    Route::get('respuestas-sunat-estadisticas', [RespuestaSunatController::class, 'estadisticas']);
    Route::get('respuestas-sunat/comprobante/{comprobanteId}', [RespuestaSunatController::class, 'porComprobante']);

    // Pagos
    Route::apiResource('pagos', PagoController::class);
    Route::post('pagos/{pago}/confirmar', [PagoController::class, 'confirmar']);
    Route::post('pagos/{pago}/anular', [PagoController::class, 'anular']);
    Route::get('pagos/comprobante/{comprobanteId}', [PagoController::class, 'porComprobante']);
    Route::get('pagos/caja/{cajaId}', [PagoController::class, 'porCaja']);
    Route::get('pagos-estadisticas', [PagoController::class, 'estadisticas']);
    Route::post('pagos-cuotas', [PagoController::class, 'registrarCuotas']);

    // Medios de pago
    Route::apiResource('medios-pago', MedioPagoController::class);
    Route::patch('medios-pago/{medioPago}/toggle-status', [MedioPagoController::class, 'toggleStatus']);
    Route::get('medios-pago-constantes', [MedioPagoController::class, 'constantes']);
    Route::get('medios-pago-efectivo', [MedioPagoController::class, 'efectivo']);
    Route::get('medios-pago-transferencia', [MedioPagoController::class, 'transferencia']);
    Route::get('medios-pago/{medioPago}/requiere-referencia', [MedioPagoController::class, 'requiereReferencia']);
    Route::get('medios-pago-estadisticas', [MedioPagoController::class, 'estadisticas']);
    Route::get('medios-pago-mas-usados', [MedioPagoController::class, 'masUsados']);

    // Libros electrónicos
    Route::apiResource('libros-electronicos', LibroElectronicoController::class);
    Route::post('libros-electronicos/{libroElectronico}/generar', [LibroElectronicoController::class, 'generar']);
    Route::post('libros-electronicos-generar-nuevo', [LibroElectronicoController::class, 'generarNuevo']);
    Route::get('libros-electronicos/{libroElectronico}/descargar', [LibroElectronicoController::class, 'descargar']);
    Route::post('libros-electronicos/{libroElectronico}/marcar-enviado', [LibroElectronicoController::class, 'marcarEnviado']);
    Route::post('libros-electronicos/{libroElectronico}/marcar-rechazado', [LibroElectronicoController::class, 'marcarRechazado']);
    Route::get('libros-electronicos-tipos', [LibroElectronicoController::class, 'tipos']);
    Route::get('libros-electronicos-pendientes-envio', [LibroElectronicoController::class, 'pendientesEnvio']);
    Route::get('libros-electronicos-estadisticas', [LibroElectronicoController::class, 'estadisticas']);
    Route::post('libros-electronicos/{libroElectronico}/regenerar', [LibroElectronicoController::class, 'regenerar']);

    // Empresas
    Route::apiResource('empresas', EmpresaController::class);
    Route::post('empresas-validar-ruc', [EmpresaController::class, 'validarRuc']);
    Route::get('empresas/{empresa}/verificar-certificado', [EmpresaController::class, 'verificarCertificado']);
    Route::put('empresas/{empresa}/actualizar-certificado', [EmpresaController::class, 'actualizarCertificado']);
    Route::put('empresas/{empresa}/actualizar-credenciales-sol', [EmpresaController::class, 'actualizarCredencialesSol']);
    Route::patch('empresas/{empresa}/cambiar-modo', [EmpresaController::class, 'cambiarModo']);
    Route::patch('empresas/{empresa}/toggle-pse', [EmpresaController::class, 'togglePse']);
    Route::get('empresas/{empresa}/estadisticas', [EmpresaController::class, 'estadisticas']);
    Route::delete('empresas/{empresa}/logo', [EmpresaController::class, 'eliminarLogo']);

    // Configuraciones empresa
    Route::apiResource('configuraciones-empresa', ConfiguracionEmpresaController::class);
    Route::get('configuraciones-empresa-por-empresa/{empresaId}', [ConfiguracionEmpresaController::class, 'porEmpresa']);
    Route::post('configuraciones-empresa/{configuracionEmpresa}/calcular-igv', [ConfiguracionEmpresaController::class, 'calcularIgv']);
    Route::post('configuraciones-empresa/{configuracionEmpresa}/calcular-sin-igv', [ConfiguracionEmpresaController::class, 'calcularSinIgv']);
    Route::post('configuraciones-empresa/{configuracionEmpresa}/calcular-retencion', [ConfiguracionEmpresaController::class, 'calcularRetencion']);
    Route::post('configuraciones-empresa/{configuracionEmpresa}/calcular-percepcion', [ConfiguracionEmpresaController::class, 'calcularPercepcion']);
    Route::patch('configuraciones-empresa/{configuracionEmpresa}/actualizar-igv', [ConfiguracionEmpresaController::class, 'actualizarIgv']);
    Route::patch('configuraciones-empresa/{configuracionEmpresa}/actualizar-moneda', [ConfiguracionEmpresaController::class, 'actualizarMoneda']);
    Route::post('configuraciones-empresa/{configuracionEmpresa}/restablecer-defecto', [ConfiguracionEmpresaController::class, 'restablecerDefecto']);
    Route::get('monedas-disponibles', [ConfiguracionEmpresaController::class, 'monedasDisponibles']);
    Route::put('configuracion-empresa/{id}', [ConfiguracionEmpresaController::class, 'update']);

    // Configuraciones
    Route::apiResource('configuraciones', ConfiguracionController::class);
    Route::get('configuraciones-obtener-por-clave', [ConfiguracionController::class, 'obtenerPorClave']);
    Route::post('configuraciones-establecer', [ConfiguracionController::class, 'establecer']);
    Route::get('configuraciones-por-empresa/{empresaId}', [ConfiguracionController::class, 'porEmpresa']);
    Route::get('configuraciones-globales', [ConfiguracionController::class, 'globales']);
    Route::get('configuraciones-por-tipo/{tipo}', [ConfiguracionController::class, 'porTipo']);
    Route::post('configuraciones-actualizar-multiples', [ConfiguracionController::class, 'actualizarMultiples']);
    Route::delete('configuraciones-eliminar-por-empresa/{empresaId}', [ConfiguracionController::class, 'eliminarPorEmpresa']);
    Route::get('configuraciones-buscar', [ConfiguracionController::class, 'buscar']);
    Route::get('configuraciones-exportar', [ConfiguracionController::class, 'exportar']);
    Route::post('configuraciones-importar', [ConfiguracionController::class, 'importar']);

    // Clientes
    Route::apiResource('clientes', ClienteController::class);
    Route::patch('clientes/{cliente}/toggle-estado', [ClienteController::class, 'toggleEstado']);
    Route::get('clientes-buscar-por-documento', [ClienteController::class, 'buscarPorDocumento']);
    Route::get('clientes/{cliente}/deuda', [ClienteController::class, 'deuda']);
    Route::get('clientes/{cliente}/comprobantes', [ClienteController::class, 'comprobantes']);
    Route::get('clientes/{cliente}/estadisticas', [ClienteController::class, 'estadisticas']);
    Route::get('clientes-top', [ClienteController::class, 'topClientes']);
    Route::get('clientes-con-deuda', [ClienteController::class, 'conDeuda']);
    Route::post('clientes-importar', [ClienteController::class, 'importar']);

    // Cajas
    Route::apiResource('cajas', CajaController::class);
    Route::post('cajas/{caja}/cerrar', [CajaController::class, 'cerrar']);
    Route::get('caja-abierta', [CajaController::class, 'cajaAbierta']);
    Route::get('cajas/{caja}/resumen', [CajaController::class, 'resumen']);
    Route::get('cajas/{caja}/validar-cuadratura', [CajaController::class, 'validarCuadratura']);
    Route::get('cajas-del-dia', [CajaController::class, 'delDia']);
    Route::get('cajas-del-usuario/{usuarioId}', [CajaController::class, 'delUsuario']);
    Route::get('cajas-estadisticas', [CajaController::class, 'estadisticas']);
    Route::post('cajas/{caja}/reabrir', [CajaController::class, 'reabrir']);

    // Auditorías
    Route::get('auditorias', [AuditoriaController::class, 'index']);
    Route::post('auditorias', [AuditoriaController::class, 'store']);
    Route::get('auditorias/{auditoria}', [AuditoriaController::class, 'show']);
    Route::get('auditorias-por-registro', [AuditoriaController::class, 'porRegistro']);
    Route::get('auditorias-por-tabla/{tabla}', [AuditoriaController::class, 'porTabla']);
    Route::get('auditorias-por-usuario/{usuarioId}', [AuditoriaController::class, 'porUsuario']);
    Route::get('auditorias-recientes', [AuditoriaController::class, 'recientes']);
    Route::get('auditorias-tablas', [AuditoriaController::class, 'tablas']);
    Route::get('auditorias-acciones', [AuditoriaController::class, 'acciones']);
    Route::get('auditorias-estadisticas', [AuditoriaController::class, 'estadisticas']);
    Route::get('auditorias-actividad-usuario/{usuarioId}', [AuditoriaController::class, 'actividadUsuario']);
    Route::get('auditorias-historial', [AuditoriaController::class, 'historial']);
    Route::get('auditorias-comparar/{auditoriaId1}/{auditoriaId2}', [AuditoriaController::class, 'comparar']);
    Route::delete('auditorias-limpiar', [AuditoriaController::class, 'limpiar']);

    // Inventario - Transferencias de stock
    Route::apiResource('inventario/transferencias-stock', TransferenciaStockController::class);
    Route::post('inventario/transferencias-stock/{transferenciaStock}/aplicar', [TransferenciaStockController::class, 'aplicar']);
    Route::post('inventario/transferencias-stock/{transferenciaStock}/anular', [TransferenciaStockController::class, 'anular']);
    Route::get('inventario/transferencias-stock/{transferenciaStock}/movimientos', [TransferenciaStockController::class, 'movimientos']);
    Route::post('inventario/transferencias-stock-validar-stock', [TransferenciaStockController::class, 'validarStock']);
    Route::get('inventario/transferencias-stock-estadisticas', [TransferenciaStockController::class, 'estadisticas']);
    Route::get('inventario/transferencias-stock-entre-almacenes/{almacenOrigenId}/{almacenDestinoId}', [TransferenciaStockController::class, 'entreAlmacenes']);

    // Inventario - Productos
    Route::apiResource('inventario/productos', ProductoController::class);
    Route::patch('inventario/productos/{producto}/toggle-estado', [ProductoController::class, 'toggleEstado']);
    Route::put('inventario/productos/{producto}/actualizar-precios', [ProductoController::class, 'actualizarPrecios']);
    Route::get('inventario/productos/{producto}/stock-por-almacen', [ProductoController::class, 'stockPorAlmacen']);
    Route::get('inventario/productos-bajo-stock', [ProductoController::class, 'bajoStock']);
    Route::get('inventario/productos/{producto}/calcular-costos', [ProductoController::class, 'calcularCostos']);
    Route::get('inventario/productos/{producto}/movimientos', [ProductoController::class, 'movimientos']);
    Route::get('inventario/productos/{producto}/estadisticas', [ProductoController::class, 'estadisticas']);
    Route::post('inventario/productos-importar', [ProductoController::class, 'importar']);

    // Inventario - Movimientos de stock
    Route::get('inventario/movimientos-stock', [MovimientoStockController::class, 'index']);
    Route::post('inventario/movimientos-stock', [MovimientoStockController::class, 'store']);
    Route::get('inventario/movimientos-stock/{movimientoStock}', [MovimientoStockController::class, 'show']);
    Route::get('inventario/movimientos-stock-por-producto/{productoId}', [MovimientoStockController::class, 'porProducto']);
    Route::get('inventario/movimientos-stock-por-almacen/{almacenId}', [MovimientoStockController::class, 'porAlmacen']);
    Route::get('inventario/movimientos-stock-entradas', [MovimientoStockController::class, 'entradas']);
    Route::get('inventario/movimientos-stock-salidas', [MovimientoStockController::class, 'salidas']);
    Route::get('inventario/movimientos-stock-estadisticas', [MovimientoStockController::class, 'estadisticas']);
    Route::get('inventario/movimientos-stock-kardex/{productoId}', [MovimientoStockController::class, 'kardex']);
    Route::get('inventario/movimientos-stock-resumen-por-almacen', [MovimientoStockController::class, 'resumenPorAlmacen']);
    Route::get('inventario/movimientos-stock-recientes', [MovimientoStockController::class, 'recientes']);
    Route::get('inventario/movimientos-stock-valorizacion', [MovimientoStockController::class, 'valorizacion']);

    // Inventario - Categorías
    Route::apiResource('inventario/categorias', CategoriaController::class);
    Route::get('inventario/categorias/{categoria}/productos', [CategoriaController::class, 'productos']);
    Route::get('inventario/categorias/{categoria}/estadisticas', [CategoriaController::class, 'estadisticas']);
    Route::get('inventario/categorias-con-mas-productos', [CategoriaController::class, 'conMasProductos']);
    Route::get('inventario/categorias-sin-productos', [CategoriaController::class, 'sinProductos']);
    Route::get('inventario/categorias/{categoria}/verificar-eliminacion', [CategoriaController::class, 'verificarEliminacion']);
    Route::post('inventario/categorias/{categoria}/mover-productos', [CategoriaController::class, 'moverProductos']);
    Route::post('inventario/categorias-importar', [CategoriaController::class, 'importar']);

    // Inventario - Almacén Productos
    Route::apiResource('inventario/almacen-productos', AlmacenProductoController::class);
    Route::post('inventario/almacen-productos/{almacenProducto}/ajustar-stock', [AlmacenProductoController::class, 'ajustarStock']);
    Route::get('inventario/almacen-productos-stock-por-producto/{productoId}', [AlmacenProductoController::class, 'stockPorProducto']);
    Route::get('inventario/almacen-productos-por-almacen/{almacenId}', [AlmacenProductoController::class, 'productosPorAlmacen']);
    Route::get('inventario/almacen-productos-bajo-stock', [AlmacenProductoController::class, 'bajoStock']);
    Route::get('inventario/almacen-productos-sin-stock', [AlmacenProductoController::class, 'sinStock']);
    Route::get('inventario/almacen-productos-valorizacion', [AlmacenProductoController::class, 'valorizacion']);
    Route::post('inventario/almacen-productos-verificar-disponibilidad', [AlmacenProductoController::class, 'verificarDisponibilidad']);
    Route::post('inventario/almacen-productos-transferir', [AlmacenProductoController::class, 'transferir']);
    Route::get('inventario/almacen-productos-estadisticas', [AlmacenProductoController::class, 'estadisticas']);

    // Inventario - Almacenes
    Route::apiResource('inventario/almacenes', AlmacenController::class);
    Route::patch('inventario/almacenes/{almacen}/toggle-estado', [AlmacenController::class, 'toggleEstado']);
    Route::get('inventario/almacenes/{almacen}/productos', [AlmacenController::class, 'productos']);
    Route::post('inventario/almacenes/{almacen}/verificar-stock', [AlmacenController::class, 'verificarStock']);
    Route::get('inventario/almacenes/{almacen}/productos-bajo-stock', [AlmacenController::class, 'productosConBajoStock']);
    Route::get('inventario/almacenes/{almacen}/movimientos', [AlmacenController::class, 'movimientos']);
    Route::get('inventario/almacenes/{almacen}/estadisticas', [AlmacenController::class, 'estadisticas']);
    Route::get('inventario/almacenes/{almacen}/valorizacion', [AlmacenController::class, 'valorizacion']);
    Route::post('inventario/almacenes-comparar', [AlmacenController::class, 'comparar']);
    Route::delete('inventario/almacenes/{almacenId}/limpiar-cache', [AlmacenController::class, 'limpiarCache']);
    Route::post('inventario/almacenes-importar', [AlmacenController::class, 'importar']);

    // Inventario - Ajustes de inventario
    Route::apiResource('inventario/ajustes-inventario', AjusteInventarioController::class);
    Route::post('inventario/ajustes-inventario/{ajusteInventario}/aplicar', [AjusteInventarioController::class, 'aplicar']);
    Route::post('inventario/ajustes-inventario/{ajusteInventario}/anular', [AjusteInventarioController::class, 'anular']);
    Route::get('inventario/ajustes-inventario/{ajusteInventario}/movimientos', [AjusteInventarioController::class, 'movimientos']);
    Route::get('inventario/ajustes-inventario-estadisticas', [AjusteInventarioController::class, 'estadisticas']);
    Route::get('inventario/ajustes-inventario-del-mes', [AjusteInventarioController::class, 'delMes']);
    Route::get('inventario/ajustes-inventario-por-almacen/{almacenId}', [AjusteInventarioController::class, 'porAlmacen']);
    Route::get('inventario/ajustes-inventario-tipos', [AjusteInventarioController::class, 'tipos']);
    Route::get('inventario/ajustes-inventario-resumen', [AjusteInventarioController::class, 'resumen']);

    // Facturación - Series
    Route::apiResource('facturacion/series', SerieController::class);
    Route::patch('facturacion/series/{serie}/toggle-estado', [SerieController::class, 'toggleEstado']);
    Route::post('facturacion/series/{serie}/generar-numero', [SerieController::class, 'generarNumero']);
    Route::get('facturacion/series/{serie}/siguiente-numero', [SerieController::class, 'siguienteNumero']);
    Route::get('facturacion/series/{serie}/validar-formato', [SerieController::class, 'validarFormato']);
    Route::get('facturacion/series-por-tipo/{tipoComprobante}', [SerieController::class, 'porTipo']);
    Route::put('facturacion/series/{serie}/restablecer-correlativo', [SerieController::class, 'restablecerCorrelativo']);
    Route::get('facturacion/series-estadisticas', [SerieController::class, 'estadisticas']);
    Route::get('facturacion/series-tipos', [SerieController::class, 'tipos']);

    // Facturación - Guías de remisión
    Route::apiResource('facturacion/guias-remision', GuiaRemisionController::class);
    Route::post('facturacion/guias-remision/{guiaRemision}/anular', [GuiaRemisionController::class, 'anular']);
    Route::get('facturacion/guias-remision/{guiaRemision}/validar', [GuiaRemisionController::class, 'validar']);
    Route::get('facturacion/guias-remision-siguiente-numero', [GuiaRemisionController::class, 'siguienteNumero']);
    Route::get('facturacion/guias-remision-por-comprobante/{comprobanteId}', [GuiaRemisionController::class, 'porComprobante']);
    Route::get('facturacion/guias-remision-estadisticas', [GuiaRemisionController::class, 'estadisticas']);
    Route::get('facturacion/guias-remision-motivos', [GuiaRemisionController::class, 'motivos']);
    Route::get('facturacion/guias-remision-del-mes', [GuiaRemisionController::class, 'delMes']);
    Route::get('facturacion/guias-remision-series', [GuiaRemisionController::class, 'series']);
    Route::get('facturacion/guias-remision-exportar', [GuiaRemisionController::class, 'exportar']);

    // Facturación - Comprobante Detalles
    Route::apiResource('facturacion/comprobante-detalles', ComprobanteDetalleController::class);
    Route::get('facturacion/comprobante-detalles/{comprobanteDetalle}/validar-total', [ComprobanteDetalleController::class, 'validarTotal']);
    Route::post('facturacion/comprobante-detalles/{comprobanteDetalle}/recalcular', [ComprobanteDetalleController::class, 'recalcular']);
    Route::get('facturacion/comprobante-detalles-por-comprobante/{comprobanteId}', [ComprobanteDetalleController::class, 'porComprobante']);
    Route::get('facturacion/comprobante-detalles-por-producto/{productoId}', [ComprobanteDetalleController::class, 'porProducto']);
    Route::get('facturacion/comprobante-detalles-productos-mas-vendidos', [ComprobanteDetalleController::class, 'productosMasVendidos']);
    Route::get('facturacion/comprobante-detalles-estadisticas', [ComprobanteDetalleController::class, 'estadisticas']);
    Route::post('facturacion/comprobante-detalles-aplicar-descuento-masivo', [ComprobanteDetalleController::class, 'aplicarDescuentoMasivo']);

    // Facturación - Comprobantes
    Route::apiResource('facturacion/comprobantes', ComprobanteController::class);
    Route::post('facturacion/comprobantes/{comprobante}/anular', [ComprobanteController::class, 'anular']);
    Route::post('facturacion/comprobantes/{comprobante}/recalcular-totales', [ComprobanteController::class, 'recalcularTotales']);
    Route::post('facturacion/comprobantes/{comprobante}/actualizar-saldo', [ComprobanteController::class, 'actualizarSaldo']);
    Route::get('facturacion/comprobantes-con-saldo', [ComprobanteController::class, 'conSaldo']);
    Route::get('facturacion/comprobantes-estadisticas', [ComprobanteController::class, 'estadisticas']);
    Route::get('facturacion/comprobantes-exportar', [ComprobanteController::class, 'exportar']);
    Route::get('facturacion/comprobantes-ventas-del-dia', [ComprobanteController::class, 'ventasDelDia']);
    Route::post('facturacion/comprobantes-crear-nota-credito', [ComprobanteController::class, 'crearNotaCredito']);

    // Plan de Cuentas
    Route::apiResource('contabilidad/plan-cuentas', PlanCuentaController::class);
    Route::get('contabilidad/plan-cuentas-arbol', [PlanCuentaController::class, 'arbol']);
    Route::get('contabilidad/plan-cuentas/{planCuentaId}/hijos', [PlanCuentaController::class, 'hijos']);
    Route::get('contabilidad/plan-cuentas/{planCuenta}/ruta', [PlanCuentaController::class, 'ruta']);
    Route::get('contabilidad/plan-cuentas/{planCuenta}/saldo', [PlanCuentaController::class, 'saldo']);
    Route::get('contabilidad/plan-cuentas-balance-general', [PlanCuentaController::class, 'balanceGeneral']);
    Route::get('contabilidad/plan-cuentas-estado-resultados', [PlanCuentaController::class, 'estadoResultados']);
    Route::get('contabilidad/plan-cuentas-por-tipo/{tipo}', [PlanCuentaController::class, 'porTipo']);
    Route::get('contabilidad/plan-cuentas-auxiliares', [PlanCuentaController::class, 'auxiliares']);
    Route::get('contabilidad/plan-cuentas-estadisticas', [PlanCuentaController::class, 'estadisticas']);
    Route::post('contabilidad/plan-cuentas-importar', [PlanCuentaController::class, 'importar']);
    Route::post('contabilidad/plan-cuentas/{planCuenta}/validar-padre', [PlanCuentaController::class, 'validarPadre']);
    Route::get('contabilidad/plan-cuentas-tipos', [PlanCuentaController::class, 'tipos']);

    // Períodos Contables
    Route::apiResource('contabilidad/periodos-contables', PeriodoContableController::class);
    Route::post('contabilidad/periodos-contables/{periodoContable}/cerrar', [PeriodoContableController::class, 'cerrar']);
    Route::post('contabilidad/periodos-contables/{periodoContable}/reabrir', [PeriodoContableController::class, 'reabrir']);
    Route::get('contabilidad/periodos-contables-actual', [PeriodoContableController::class, 'actual']);
    Route::get('contabilidad/periodos-contables-abiertos', [PeriodoContableController::class, 'abiertos']);
    Route::get('contabilidad/periodos-contables-cerrados', [PeriodoContableController::class, 'cerrados']);
    Route::get('contabilidad/periodos-contables-del-año/{año}', [PeriodoContableController::class, 'delAño']);
    Route::post('contabilidad/periodos-contables/{periodoContable}/generar-libros', [PeriodoContableController::class, 'generarLibros']);
    Route::get('contabilidad/periodos-contables/{periodoContable}/asientos', [PeriodoContableController::class, 'asientos']);
    Route::get('contabilidad/periodos-contables/{periodoContable}/estadisticas', [PeriodoContableController::class, 'estadisticas']);
    Route::post('contabilidad/periodos-contables-crear-multiples', [PeriodoContableController::class, 'crearMultiples']);
    Route::post('contabilidad/periodos-contables-crear-año-completo', [PeriodoContableController::class, 'crearAñoCompleto']);
    Route::get('contabilidad/periodos-contables/{periodoContable}/verificar-cierre', [PeriodoContableController::class, 'verificarCierre']);
    Route::get('contabilidad/periodos-contables/{periodoContable}/verificar-reapertura', [PeriodoContableController::class, 'verificarReapertura']);
    Route::get('contabilidad/periodos-contables-resumen', [PeriodoContableController::class, 'resumen']);

    // Diarios Contables
    Route::apiResource('contabilidad/diarios', DiarioController::class);
    Route::patch('contabilidad/diarios/{diario}/toggle-estado', [DiarioController::class, 'toggleEstado']);
    Route::post('contabilidad/diarios/{diario}/generar-numero', [DiarioController::class, 'generarNumero']);
    Route::get('contabilidad/diarios/{diario}/siguiente-numero', [DiarioController::class, 'siguienteNumero']);
    Route::put('contabilidad/diarios/{diario}/restablecer-correlativo', [DiarioController::class, 'restablecerCorrelativo']);
    Route::get('contabilidad/diarios/{diario}/asientos', [DiarioController::class, 'asientos']);
    Route::get('contabilidad/diarios/{diario}/estadisticas', [DiarioController::class, 'estadisticas']);
    Route::get('contabilidad/diarios-manuales', [DiarioController::class, 'manuales']);
    Route::get('contabilidad/diarios-automaticos', [DiarioController::class, 'automaticos']);
    Route::get('contabilidad/diarios-estadisticas-generales', [DiarioController::class, 'estadisticasGenerales']);
    Route::post('contabilidad/diarios-crear-por-defecto', [DiarioController::class, 'crearPorDefecto']);
    Route::get('contabilidad/diarios-tipos', [DiarioController::class, 'tipos']);
    Route::post('contabilidad/diarios-validar-codigo', [DiarioController::class, 'validarCodigo']);
    Route::get('contabilidad/diarios-exportar', [DiarioController::class, 'exportar']);

    // Asientos Contables
    Route::apiResource('contabilidad/asientos', AsientoController::class);
    Route::post('contabilidad/asientos/{asiento}/registrar', [AsientoController::class, 'registrar']);
    Route::post('contabilidad/asientos/{asiento}/anular', [AsientoController::class, 'anular']);
    Route::post('contabilidad/asientos/{asiento}/duplicar', [AsientoController::class, 'duplicar']);
    Route::post('contabilidad/asientos/{asiento}/recalcular-totales', [AsientoController::class, 'recalcularTotales']);
    Route::get('contabilidad/asientos/{asiento}/validar-cuadre', [AsientoController::class, 'validarCuadre']);
    Route::post('contabilidad/asientos-generar-desde-comprobante', [AsientoController::class, 'generarDesdeComprobante']);
    Route::get('contabilidad/asientos-borradores', [AsientoController::class, 'borradores']);
    Route::get('contabilidad/asientos-registrados', [AsientoController::class, 'registrados']);
    Route::get('contabilidad/asientos-por-periodo/{periodoId}', [AsientoController::class, 'porPeriodo']);
    Route::get('contabilidad/asientos-por-diario/{diarioId}', [AsientoController::class, 'porDiario']);
    Route::get('contabilidad/asientos-estadisticas', [AsientoController::class, 'estadisticas']);
    Route::get('contabilidad/asientos-libro-diario', [AsientoController::class, 'libroDiario']);
    Route::get('contabilidad/asientos-exportar', [AsientoController::class, 'exportar']);
    Route::get('contabilidad/asientos-descuadrados', [AsientoController::class, 'descuadrados']);
    Route::post('contabilidad/asientos-registrar-multiples', [AsientoController::class, 'registrarMultiples']);

    // Detalles de Asientos Contables
    Route::apiResource('contabilidad/asiento-detalles', AsientoDetalleController::class);
    Route::post('contabilidad/asiento-detalles/{asientoDetalle}/cambiar-tipo', [AsientoDetalleController::class, 'cambiarTipo']);
    Route::get('contabilidad/asiento-detalles-por-asiento/{asientoId}', [AsientoDetalleController::class, 'porAsiento']);
    Route::get('contabilidad/asiento-detalles-por-cuenta/{cuentaId}', [AsientoDetalleController::class, 'porCuenta']);
    Route::get('contabilidad/asiento-detalles-cargos', [AsientoDetalleController::class, 'cargos']);
    Route::get('contabilidad/asiento-detalles-abonos', [AsientoDetalleController::class, 'abonos']);
    Route::get('contabilidad/asiento-detalles-estadisticas', [AsientoDetalleController::class, 'estadisticas']);
    Route::get('contabilidad/asiento-detalles-validar-cuadre/{asientoId}', [AsientoDetalleController::class, 'validarCuadre']);
    Route::post('contabilidad/asiento-detalles/{asientoDetalle}/duplicar', [AsientoDetalleController::class, 'duplicar']);
    Route::get('contabilidad/asiento-detalles-mayor-contable', [AsientoDetalleController::class, 'mayorContable']);
    Route::get('contabilidad/asiento-detalles-exportar', [AsientoDetalleController::class, 'exportar']);

    // Proveedores
    Route::apiResource('compras/proveedores', ProveedorController::class);
    Route::patch('compras/proveedores/{proveedor}/toggle-estado', [ProveedorController::class, 'toggleEstado']);
    Route::post('compras/proveedores/{id}/restore', [ProveedorController::class, 'restore']);
    Route::get('compras/proveedores/{proveedor}/compras', [ProveedorController::class, 'compras']);
    Route::get('compras/proveedores-top', [ProveedorController::class, 'topProveedores']);
    Route::get('compras/proveedores-estadisticas', [ProveedorController::class, 'estadisticas']);
    Route::get('compras/proveedores-por-tipo-documento/{tipoDocumento}', [ProveedorController::class, 'porTipoDocumento']);
    Route::get('compras/proveedores-buscar-por-documento', [ProveedorController::class, 'buscarPorDocumento']);
    Route::post('compras/proveedores-importar', [ProveedorController::class, 'importar']);
    Route::get('compras/proveedores-exportar', [ProveedorController::class, 'exportar']);
    Route::get('compras/proveedores-tipos-documento', [ProveedorController::class, 'tiposDocumento']);
    Route::get('compras/proveedores-eliminados', [ProveedorController::class, 'eliminados']);

    // Compras
    Route::apiResource('compras/compras', CompraController::class);
    Route::post('compras/compras/{compra}/anular', [CompraController::class, 'anular']);
    Route::post('compras/compras/{compra}/recalcular-total', [CompraController::class, 'recalcularTotal']);
    Route::get('compras/compras-registradas', [CompraController::class, 'registradas']);
    Route::get('compras/compras-anuladas', [CompraController::class, 'anuladas']);
    Route::get('compras/compras-por-proveedor/{proveedorId}', [CompraController::class, 'porProveedor']);
    Route::get('compras/compras-por-almacen/{almacenId}', [CompraController::class, 'porAlmacen']);
    Route::get('compras/compras-del-periodo', [CompraController::class, 'delPeriodo']);
    Route::get('compras/compras-estadisticas', [CompraController::class, 'estadisticas']);
    Route::get('compras/compras-del-mes', [CompraController::class, 'delMes']);
    Route::get('compras/compras-del-dia', [CompraController::class, 'delDia']);
    Route::get('compras/compras-exportar', [CompraController::class, 'exportar']);
    Route::get('compras/compras/{compra}/detalles', [CompraController::class, 'detalles']);
    Route::get('compras/compras-reporte', [CompraController::class, 'reporte']);
    Route::get('compras/compras/{compra}/verificar-stock', [CompraController::class, 'verificarStock']);

    // Detalles de Compras
    Route::apiResource('compras/compra-detalles', CompraDetalleController::class);
    Route::post('compras/compra-detalles/{compraDetalle}/recalcular-subtotal', [CompraDetalleController::class, 'recalcularSubtotal']);
    Route::get('compras/compra-detalles-por-compra/{compraId}', [CompraDetalleController::class, 'porCompra']);
    Route::get('compras/compra-detalles-por-producto/{productoId}', [CompraDetalleController::class, 'porProducto']);
    Route::get('compras/compra-detalles-productos-mas-comprados', [CompraDetalleController::class, 'productosMasComprados']);
    Route::get('compras/compra-detalles-estadisticas', [CompraDetalleController::class, 'estadisticas']);
    Route::get('compras/compra-detalles-historial-precios/{productoId}', [CompraDetalleController::class, 'historialPrecios']);
    Route::get('compras/compra-detalles-exportar', [CompraDetalleController::class, 'exportar']);
    Route::get('compras/compra-detalles-comparar-precios/{productoId}', [CompraDetalleController::class, 'compararPrecios']);
    Route::get('compras/compra-detalles-ultimas-compras/{productoId}', [CompraDetalleController::class, 'ultimasCompras']);
    Route::post('compras/compra-detalles-validar-precios', [CompraDetalleController::class, 'validarPrecios']);
});
