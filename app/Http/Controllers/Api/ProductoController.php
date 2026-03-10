<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    /**
     * Listar todos los productos con sus categorías y variantes.
     */
    public function index()
    {
        $productos = Producto::with(['categoria', 'variantes.material', 'imagenes'])
            ->where('activo', 1)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $productos
        ], 200);
    }

    /**
     * Mostrar un producto específico.
     */
    public function show($id)
    {
        $producto = Producto::with(['categoria', 'variantes.material', 'imagenes'])
            ->find($id);

        if (!$producto) {
            return response()->json([
                'status' => 'error',
                'message' => 'Producto no encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $producto
        ], 200);
    }
}
