<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\VariantesProducto;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    /**
     * Crear un nuevo pedido desde el Frontend
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.precio' => 'required|numeric',
            'total' => 'required|numeric'
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $usuario = $request->user();
                
                if (!$usuario) {
                    return response()->json(['status' => 'error', 'mensaje' => 'No se detectó usuario autenticado.'], 401);
                }

                // 1. Buscamos o creamos el registro de cliente
                $cliente = Cliente::where('usuario_id', $usuario->id)->first();
                if (!$cliente) {
                    $cliente = Cliente::create([
                        'usuario_id' => $usuario->id,
                        'telefono' => $request->telefono ?? '0000000000',
                    ]);
                }

                // 2. Creamos el pedido principal
                $pedido = Pedido::create([
                    'cliente_id' => $cliente->id,
                    'fecha_pedido' => now(),
                    'total' => $request->total,
                    'estado_pago' => 'pendiente',
                    'estado_envio' => 'procesando', // Valor válido según el ENUM de SQL
                    'notas' => 'Pedido realizado desde la web de Solare'
                ]);

                // 3. Registramos los detalles con validación de variante
                foreach ($request->items as $item) {
                    $varianteId = $item['id'];
                    
                    // Si el ID no es de una variante, buscamos la variante por defecto del producto
                    if (!VariantesProducto::where('id', $varianteId)->exists()) {
                        $fallback = VariantesProducto::where('producto_id', $varianteId)->first();
                        $varianteId = $fallback ? $fallback->id : $varianteId;
                    }

                    DetallePedido::create([
                        'pedido_id' => $pedido->id,
                        'variante_id' => $varianteId,
                        'cantidad' => $item['cantidad'],
                        'precio_unitario' => $item['precio']
                    ]);
                }

                return response()->json([
                    'status' => 'success',
                    'pedido_id' => $pedido->id,
                    'mensaje' => 'Pedido registrado correctamente.'
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'Fallo en base de datos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar pedidos del usuario autenticado
     */
    public function misPedidos(Request $request)
    {
        $usuario = $request->user();
        $cliente = Cliente::where('usuario_id', $usuario->id)->first();
        
        if (!$cliente) return response()->json([]);

        $pedidos = Pedido::with('detalles.variante.producto')
            ->where('cliente_id', $cliente->id)
            ->latest('fecha_pedido')
            ->get();

        return response()->json($pedidos);
    }
}
