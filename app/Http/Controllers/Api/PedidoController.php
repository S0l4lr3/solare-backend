<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\VarianteProducto;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    /**
     * Crear un nuevo pedido.
     */
    public function store(Request $request)
    {
        $request->validate([
            'productos' => 'required|array|min:1',
            'productos.*.variante_id' => 'required|exists:variantes_producto,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'direccion_id' => 'nullable|exists:direcciones_envio,id',
        ]);

        // Obtener el cliente asociado al usuario autenticado
        $cliente = Cliente::where('usuario_id', $request->user()->id)->first();

        if (!$cliente) {
             return response()->json(['message' => 'El usuario no está registrado como cliente'], 403);
        }

        try {
            DB::beginTransaction();

            // 1. Crear la cabecera del pedido
            $pedido = Pedido::create([
                'cliente_id' => $cliente->id,
                'direccion_envio_id' => $request->direccion_id,
                'fecha_pedido' => now(),
                'estado_pago' => 'pendiente',
                'estado_envio' => 'procesando',
                'notas' => $request->notas
            ]);

            // 2. Crear los detalles (Esto disparará el trigger de stock en la DB)
            foreach ($request->productos as $item) {
                $variante = VarianteProducto::find($item['variante_id']);
                
                // El precio se toma de la variante + precio base del producto
                $precio = $variante->producto->precio_base + $variante->precio_adicional;

                DetallePedido::create([
                    'pedido_id' => $pedido->id,
                    'variante_id' => $item['variante_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $precio
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pedido creado exitosamente',
                'pedido_id' => $pedido->id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error al procesar el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar pedidos del cliente autenticado.
     */
    public function index(Request $request)
    {
        $cliente = Cliente::where('usuario_id', $request->user()->id)->first();
        
        if (!$cliente) {
             return response()->json(['data' => []], 200);
        }

        $pedidos = Pedido::with('detalles.variante.producto')
            ->where('cliente_id', $cliente->id)
            ->get();

        return response()->json(['status' => 'success', 'data' => $pedidos]);
    }
}
