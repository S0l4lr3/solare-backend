<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\DetallePedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    /**
     * Listar todos los pedidos (Admin o Cliente propio).
     */
    public function index(Request $request)
    {
        $query = Pedido::with(['cliente', 'detalles.producto']);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        return response()->json($query->get());
    }

    /**
     * Crear un nuevo pedido con sus detalles.
     */
    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'total' => 'required|numeric'
        ]);

        return DB::transaction(function() use ($request) {
            $pedido = Pedido::create([
                'cliente_id' => $request->cliente_id,
                'total' => $request->total,
                'estado' => 'Pendiente',
                'creado_en' => now(),
                'actualizado_en' => now()
            ]);

            foreach ($request->productos as $p) {
                DetallePedido::create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => $p['id'],
                    'cantidad' => $p['cantidad'],
                    'precio_unitario' => $p['precio'], // El precio al momento de la compra
                ]);
            }

            return response()->json($pedido->load('detalles'), 201);
        });
    }

    /**
     * Actualizar estado del pedido (Confirmado, Enviado, etc.).
     */
    public function update(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);
        $pedido->update($request->only('estado'));
        return response()->json($pedido);
    }
}
