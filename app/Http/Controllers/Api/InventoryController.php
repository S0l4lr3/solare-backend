<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VariantesProducto;
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
        // 1. Iniciamos la consulta con relaciones necesarias
        $query = VariantesProducto::with(['producto.categoria', 'material']);

        // 2. Filtro por Búsqueda (Nombre de producto o SKU)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('producto', function($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%");
            })->orWhere('sku_especifico', 'LIKE', "%{$search}%");
        }

        // 3. Filtro por Categoría
        if ($request->filled('categoria_id')) {
            $query->whereHas('producto', function($q) use ($request) {
                $q->where('categoria_id', $request->categoria_id);
            });
        }

        // 4. Filtro por Material
        if ($request->filled('material_id')) {
            $query->where('material_id', $request->material_id);
        }

        // 5. Filtro por Nivel de Stock Crítico
        if ($request->has('stock_bajo')) {
            $query->where('existencias', '<', 3);
        }

        // 6. Ordenamiento (Join manual para ordenar por nombre de producto)
        $sortField = $request->get('sort', 'producto');
        $sortOrder = $request->get('order', 'asc');

        $query->join('productos', 'variantes_producto.producto_id', '=', 'productos.id')
              ->select('variantes_producto.*'); // Evitamos colisión de IDs

        if ($sortField === 'stock') {
            $query->orderBy('existencias', $sortOrder);
        } else {
            $query->orderBy('productos.nombre', $sortOrder);
        }

        $inventario = $query->get()->map(function ($v) {
            return [
                'id' => $v->id, // Importante para el PUT /inventario/{id}
                'variante_id' => $v->id,
                'producto' => $v->producto->nombre ?? 'Mueble Solare',
                'categoria' => $v->producto->categoria->nombre ?? 'Exterior',
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
     * Actualizar stock manualmente con auditoría
     */
    public function updateStock(Request $request, $id)
    {
        $request->validate([
            'tipo' => 'required|in:entrada,salida,ajuste',
            'cantidad' => 'required|integer|min:0',
            'motivo' => 'nullable|string'
        ]);

        $variante = VariantesProducto::findOrFail($id);
        $cantidadAnterior = $variante->existencias;

        try {
            return DB::transaction(function () use ($request, $variante, $cantidadAnterior) {
                
                $nuevaCantidad = $cantidadAnterior;

                if ($request->tipo === 'entrada') {
                    $nuevaCantidad += $request->cantidad;
                } elseif ($request->tipo === 'salida') {
                    $nuevaCantidad -= $request->cantidad;
                } elseif ($request->tipo === 'ajuste') {
                    $nuevaCantidad = $request->cantidad;
                }

                if ($nuevaCantidad < 0) {
                    throw new \Exception("El stock no puede ser negativo.");
                }

                $variante->update(['existencias' => $nuevaCantidad]);

                MovimientoInventario::create([
                    'variante_id' => $variante->id,
                    'tipo' => $request->tipo,
                    'cantidad' => ($request->tipo === 'ajuste') ? abs($nuevaCantidad - $cantidadAnterior) : $request->cantidad,
                    'cantidad_anterior' => $cantidadAnterior,
                    'cantidad_nueva' => $nuevaCantidad,
                    'usuario_id' => $request->user()->id ?? 1,
                    'motivo' => $request->motivo ?? 'Ajuste manual desde el Panel Admin'
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Inventario actualizado y auditado correctamente.',
                    'nuevo_stock' => $nuevaCantidad
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
