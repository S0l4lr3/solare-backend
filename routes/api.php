<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\DB;

// Nodo Central de Autenticación
Route::post('/registro', [AuthController::class, 'signIn']);
Route::post('/login', [AuthController::class, 'login']);

/**
 * Catálogo Público de Muebles
 * Accesible para todos los frentes sin token.
 */
Route::get('/productos', function (Request $request) {
    $query = App\Models\Producto::with('imagenes', 'categoria');

    // Filtrado por categoría si se envía el parámetro 'tipo'
    if ($request->has('tipo') && $request->tipo !== 'TODOS') {
        $query->whereHas('categoria', function($q) use ($request) {
            $q->where('nombre', 'LIKE', '%' . $request->tipo . '%');
        });
    }

    return response()->json($query->get());
});

Route::get('/productos/{id}', function ($id) {
    return response()->json(
        App\Models\Producto::with('imagenes', 'categoria')->find($id)
    );
});

// Zona de Red Protegida (Acceso con Bearer Token)
Route::middleware('auth:sanctum')->group(function() {
    
    Route::post('/logout', [AuthController::class, 'logout']);

    /**
     * Gestión de Inventario de Muebles de Exterior
     * Consume la vista de red de la base de datos muebleria_db.
     */
    Route::get('/inventario', function () {
        return response()->json(
            DB::table('vista_stock_actual')->get()
        );
    });

});
