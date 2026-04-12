<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VarianteProducto;
use App\Models\MovimientoInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Listar todo el inventario detallado.
     */
    public function index(Request $request)
    {
        $query = \App\Models\VariantesProducto::with(['producto.categoria', 'material']);

        // 1. Filtro por Búsqueda (Nombre de producto o SKU)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('producto', function($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%");
            })->orWhere('sku_especifico', 'LIKE', "%{$search}%");
        }

        // 2. Filtro por Categoría
        if ($request->filled('categoria_id')) {
            $query->whereHas('producto', function($q) use ($request) {
                $q->where('categoria_id', $request->categoria_id);
            });
        }

        // 3. Filtro por Material
        if ($request->filled('material_id')) {
            $query->where('material_id', $request->material_id);
        }

        // 4. Filtro por Nivel de Stock (Crítico: < 3)
        if ($request->has('stock_bajo')) {
            $query->where('existencias', '<', 3);
        }

        // 5. Ordenamiento Dinámico
        $sortField = $request->get('sort', 'producto'); // Por defecto producto
        $sortOrder = $request->get('order', 'asc');

        if ($sortField === 'stock') {
            $query->orderBy('existencias', $sortOrder);
        } elseif ($sortField === 'precio') {
            // Ordenar por precio requiere join para acceder a precio_base
            $query->join('productos', 'variantes_producto.producto_id', '=', 'productos.id')
                  ->orderBy(DB::raw('productos.precio_base + variantes_producto.precio_adicional'), $sortOrder);
        } else {
            // Por defecto alfabético del producto (requiere join)
            $query->join('productos', 'variantes_producto.producto_id', '=', 'productos.id')
                  ->orderBy('productos.nombre', $sortOrder)
                  ->select('variantes_producto.*'); // Evitar colisión de IDs
        }

        $inventario = $query->get()->map(function ($v) {
            return [
                'variante_id' => $v->id,
                'producto' => $v->producto->nombre ?? 'Mueble sin nombre',
                'material' => $v->material->nombre ?? $v->color ?? 'Base',
                'stock_total' => $v->existencias,
                'precio_venta' => ($v->producto->precio_base ?? 0) + ($v->precio_adicional ?? 0),
                'sku' => $v->sku_especifico
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $inventario
        ]);
    }

    /**
     * Actualizar stock de una variante manualmente.
     */
    public function updateStock(Request $request, $id)
    {
        $request->validate([
            'tipo' => 'required|in:entrada,salida,ajuste',
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'nullable|string'
        ]);

        $variante = VarianteProducto::findOrFail($id);
        $cantidadAnterior = $variante->existencias;

        try {
            return DB::transaction(function () use ($request, $variante, $cantidadAnterior) {
                
                $nuevaCantidad = $cantidadAnterior;

                if ($request->tipo === 'entrada') {
                    $nuevaCantidad += $request->cantidad;
                } elseif ($request->tipo === 'salida' || $request->tipo === 'ajuste') {
                    // Si es ajuste, la 'cantidad' enviada es el nuevo total
                    if ($request->tipo === 'ajuste') {
                        $nuevaCantidad = $request->cantidad;
                    } else {
                        $nuevaCantidad -= $request->cantidad;
                    }
                }

                if ($nuevaCantidad < 0) {
                    throw new \Exception("El stock no puede ser negativo.");
                }

                // 1. Actualizar Variante
                $variante->update(['existencias' => $nuevaCantidad]);

                // 2. Registrar Movimiento
                MovimientoInventario::create([
                    'variante_id' => $variante->id,
                    'tipo' => $request->tipo,
                    'cantidad' => ($request->tipo === 'ajuste') ? abs($nuevaCantidad - $cantidadAnterior) : $request->cantidad,
                    'cantidad_anterior' => $cantidadAnterior,
                    'cantidad_nueva' => $nuevaCantidad,
                    'usuario_id' => $request->user()->id,
                    'motivo' => $request->motivo ?? 'Actualización manual de almacén'
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Inventario actualizado correctamente',
                    'nuevo_stock' => $nuevaCantidad
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
