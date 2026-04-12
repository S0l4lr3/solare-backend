<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\PedidoController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\materialesController;
use App\Http\Controllers\Api\RolController;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\ImagenController;

use Illuminate\Support\Facades\Route;

// --- RUTAS ORIGINALES DE DIAGNÓSTICO ---
Route::get('/debug-db', function () {
    try {
        $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
        return response()->json([
            'database' => \Illuminate\Support\Facades\DB::getDatabaseName(),
            'tables' => $tables,
            'connection' => 'Exitosa'
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::get('/debug-volume', function () {
    $files = \Illuminate\Support\Facades\Storage::disk('public')->allFiles('productos');
    $mount_path = storage_path('app/public');

    return response()->json([
        'total_archivos' => count($files),
        'directorio_montaje' => $mount_path,
        'archivos' => $files,
        'disco_configurado' => config('filesystems.disks.public.driver')
    ]);
});

Route::get('/test-storage', function () {
    $filename = 'test-' . time() . '.txt';
    \Illuminate\Support\Facades\Storage::disk('public')->put($filename, 'Contenido de prueba para el volumen de Railway');
    return response()->json([
        'mensaje' => 'Archivo de prueba creado con éxito',
        'archivo' => $filename,
        'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($filename)
    ]);
});

// --- RUTAS PÚBLICAS ---
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'signIn']);
Route::get('/debug-dashboard', [DashboardController::class, 'index']);
Route::get('/categorias', [CategoriaController::class, 'index']);
Route::get('/productos', [ProductoController::class, 'index']);
Route::get('/productos/{id}', [ProductoController::class, 'show']);

// --- RUTAS PAYPAL ---
Route::post('/paypal/create', [\App\Http\Controllers\Api\ApiPayPalController::class, 'createPayment']);
Route::post('/paypal/capture', [\App\Http\Controllers\Api\ApiPayPalController::class, 'capturePayment']);

// --- RUTAS PROTEGIDAS (TOKEN) ---
Route::middleware('auth:sanctum')->group(function () {

    // 1. ÁREA ADMINISTRATIVA (CEO y Administrador)
    Route::middleware('role:CEO,Administrador')->group(function () {
        Route::get('/admin/usuarios', [AdminUserController::class, 'index']);
        Route::post('/admin/usuarios', [AdminUserController::class, 'store']);
        Route::patch('/admin/usuarios/{id}', [AdminUserController::class, 'editarusuarios']);
        Route::delete('/admin/usuarios/{id}', [AdminUserController::class, 'destroy']);
        Route::get('/admin/usuarios/{id}', [AdminUserController::class, 'show']);
        
        Route::get('/materiales', [materialesController::class, 'index']);
        Route::post('/materiales', [materialesController::class, 'store']);
        Route::put('/materiales/{id}', [materialesController::class, 'update']);
        Route::delete('/materiales/{id}', [materialesController::class, 'destroy']);

        Route::get('/roles', [RolController::class, 'index']);
        Route::post('/roles', [RolController::class, 'store']);
        Route::put('/roles/{id}', [RolController::class, 'update']);
        Route::delete('/roles/{id}', [RolController::class, 'destroy']);

        Route::get('/pedidos', [PedidoController::class, 'index']);
        Route::post('/pedidos', [PedidoController::class, 'store']);
        Route::put('/pedidos/{id}', [PedidoController::class, 'update']);

        Route::get('/admin/roles', function () {
            return \App\Models\Rol::all();
        });

        Route::post('/productos', [ProductoController::class, 'store']);
        Route::put('/productos/{id}', [ProductoController::class, 'update']);
        Route::delete('/productos/{id}', [ProductoController::class, 'destroy']);

        // Gestión de Imágenes (Fix: ImagenController con I mayúscula)
        Route::get('/productos/{id}/imagenes', [ImagenController::class, 'indexByProducto']);
        Route::post('/productos/{id}/imagenes', [ImagenController::class, 'store']);
        Route::patch('/imagenes/{id}/principal', [ImagenController::class, 'setPrincipal']);
        Route::delete('/imagenes/{id}', [ImagenController::class, 'destroy']);

        Route::post('/categorias', [CategoriaController::class, 'store']);
        Route::get('/categorias/{id}', [CategoriaController::class, 'show']);
        Route::put('/categorias/{id}', [CategoriaController::class, 'update']);
        Route::delete('/categorias/{id}', [CategoriaController::class, 'destroy']);

        Route::get('/pedidos/dashboard', [PedidoController::class, 'index']);
        Route::get('/Dashboard', [DashboardController::class, 'index']);

        // --- NUEVAS RUTAS DE REPORTE ---
        Route::get('/reportes/inventario/pdf', [ReporteController::class, 'exportPdf']);
        Route::get('/reportes/inventario/csv', [ReporteController::class, 'exportarCSV']);
    });

    // 2. ÁREA DE INVENTARIO (Consulta)
    Route::middleware('role:CEO,Administrador,Gerente,Supervisor,Vendedor,Almacenista')->group(function () {
        Route::get('/inventario', [InventoryController::class, 'index']);
    });

    // 3. ÁREA DE ALMACÉN (Modificación)
    Route::middleware('role:CEO,Administrador,Gerente,Almacenista')->group(function () {
        Route::put('/inventario/{id}', [InventoryController::class, 'updateStock']);
    });

    // 4. ÁREA DE REPORTES GENERALES
    Route::middleware('role:CEO,Administrador,Gerente,Supervisor')->group(function () {
        Route::get('/reportes/ventas', [ReporteController::class, 'ventasResumen']);
        Route::get('/reportes/ventas/pdf', [ReporteController::class, 'exportPdf']);
    });

    // Rutas redundantes de materiales/roles (originales del archivo)
    Route::get('/materiales', [materialesController::class, 'index']);
    Route::post('/materiales', [materialesController::class, 'store']);
    Route::put('/materiales/{id}', [materialesController::class, 'update']);
    Route::delete('/materiales/{id}', [materialesController::class, 'destroy']);
    Route::get('/roles', [RolController::class, 'index']);
    Route::post('/roles', [RolController::class, 'store']);
    Route::put('/roles/{id}', [RolController::class, 'update']);
    Route::delete('/roles/{id}', [RolController::class, 'destroy']);

});
