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
    public function index()
    {
        $inventario = VarianteProducto::with(['producto', 'material'])
            ->get();

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
