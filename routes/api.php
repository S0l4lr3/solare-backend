<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\PedidoController;
use App\Http\Controllers\Api\AdminUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Ruta de diagnóstico para verificar tablas en la nube
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

// NUEVA: Ruta para inspeccionar archivos en el Volumen
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

// Rutas públicas de autenticación
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'signIn']);

// Rutas de Catálogo (Públicas)
Route::get('/categorias', [CategoriaController::class, 'index']);
Route::get('/productos', [ProductoController::class, 'index']);
Route::get('/productos/{id}', [ProductoController::class, 'show']);

// Ruta de prueba para el volumen de Railway
Route::get('/test-storage', function () {
    $filename = 'test-' . time() . '.txt';
    \Illuminate\Support\Facades\Storage::disk('public')->put($filename, 'Contenido de prueba para el volumen de Railway');
    return response()->json([
        'mensaje' => 'Archivo de prueba creado con éxito',
        'archivo' => $filename,
        'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($filename)
    ]);
});

// Rutas protegidas (requieren token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    
    // Consulta de roles permitida para cualquier usuario autenticado
    Route::get('/admin/roles', function() {
        return \App\Models\Rol::all();
        
    });

    // Gestión de Pedidos (Clientes y Admin)
    Route::get('/pedidos', [PedidoController::class, 'index']);
    Route::post('/pedidos', [PedidoController::class, 'store']);
    Route::put('/pedidos/{id}', [PedidoController::class, 'update']);

    // RUTAS POR ROL

    // Solo CEO y Administrador (Máximo Nivel)
    Route::middleware('role:CEO,Administrador')->group(function () {
        // Gestión de Empleados
        Route::get('/admin/usuarios', [AdminUserController::class, 'index']);
        Route::post('/admin/usuarios', [AdminUserController::class, 'store']);
        Route::put('/admin/usuarios/{id}', [AdminUserController::class, 'update']);
        Route::delete('/admin/usuarios/{id}', [AdminUserController::class, 'destroy']);
        
        // Nueva ruta para obtener roles
        Route::get('/admin/roles', function() {
            return \App\Models\Rol::all();
        });
        
        // Gestión de Productos y Categorías completa
        Route::post('/productos', [ProductoController::class, 'store']);
        Route::put('/productos/{id}', [ProductoController::class, 'update']);
        Route::delete('/productos/{id}', [ProductoController::class, 'destroy']);
        Route::post('/categorias', [CategoriaController::class, 'store']);
        Route::put('/categorias/{id}', [CategoriaController::class, 'update']);
        Route::delete('/categorias/{id}', [CategoriaController::class, 'destroy']);

        //rutas de visualizacion de pedidos y dashboard
        Route::get('/pedidos/dashboard', [PedidoController::class, 'dashboard']);
    });

    // Rutas de Inventario (Consulta)
    Route::middleware('role:CEO,Administrador,Gerente,Supervisor,Vendedor,Almacenista')->group(function () {
        Route::get('/inventario', [\App\Http\Controllers\Api\InventoryController::class, 'index']);
    });

    // Rutas de Almacén (Modificación de stock)
    Route::middleware('role:CEO,Administrador,Gerente,Almacenista')->group(function () {
        Route::put('/inventario/{id}', [\App\Http\Controllers\Api\InventoryController::class, 'updateStock']);
    });

    // Rutas de Reportes
    Route::middleware('role:CEO,Administrador,Gerente,Supervisor')->group(function () {
        Route::get('/reportes/ventas', [\App\Http\Controllers\Api\ReporteController::class, 'ventasResumen']);
        Route::get('/reportes/ventas/pdf', [\App\Http\Controllers\Api\ReporteController::class, 'exportPdf']);
    });
});
