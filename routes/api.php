<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/registro', [AuthController::class, 'signIn']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function(){

Route::delete('logout',[AuthController::class,'logout']);
});
// Rutas públicas de autenticación
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Rutas de Catálogo (Públicas)
Route::get('/categorias', [\App\Http\Controllers\Api\CategoriaController::class, 'index']);
Route::get('/productos', [\App\Http\Controllers\Api\ProductoController::class, 'index']);
Route::get('/productos/{id}', [\App\Http\Controllers\Api\ProductoController::class, 'show']);

// Rutas protegidas (requieren token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    
    // Rutas de Pedidos (Clientes y Admin)
    Route::get('/pedidos', [\App\Http\Controllers\Api\PedidoController::class, 'index']);
    Route::post('/pedidos', [\App\Http\Controllers\Api\PedidoController::class, 'store']);
    
    // Ejemplo de ruta protegida por rol (solo Administrador)
    Route::middleware('role:Administrador')->group(function () {
        Route::get('/admin/dashboard', function () {
            return response()->json(['message' => 'Bienvenido, Administrador']);
        });
        Route::get('/admin/roles', [AuthController::class, 'getRoles']);
        Route::post('/admin/crear-empleado', [AuthController::class, 'storeEmployee']);
    });

    // Rutas de Inventario (Consulta General para Personal Interno)
    Route::middleware('role:Administrador,Gerente,Supervisor,Vendedor,Almacenista')->group(function () {
        Route::get('/inventario', [\App\Http\Controllers\Api\InventoryController::class, 'index']);
    });

    // Rutas de Almacén (Solo los que pueden MODIFICAR stock)
    Route::middleware('role:Administrador,Gerente,Almacenista')->group(function () {
        Route::put('/inventario/{id}', [\App\Http\Controllers\Api\InventoryController::class, 'updateStock']);
    });

    // Rutas de Reportes (Gerencia y Admin)
    Route::middleware('role:Administrador,Gerente,Supervisor')->group(function () {
        Route::get('/reportes/ventas', [\App\Http\Controllers\Api\ReporteController::class, 'ventasResumen']);
        Route::get('/reportes/ventas/pdf', [\App\Http\Controllers\Api\ReporteController::class, 'exportPdf']);
    });
});
