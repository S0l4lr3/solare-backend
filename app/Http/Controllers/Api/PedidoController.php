<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    public function index()
    {
        $pedidos = Pedido::with([
            'cliente.usuario',
            'direccionEnvio'
        ])->latest('creado_en')->get();

        $pedidos->transform(function ($pedido) {
            $nombreCliente = 'Sin nombre';
            if ($pedido->cliente && $pedido->cliente->usuario) {
                $nombreCliente = trim(
                    $pedido->cliente->usuario->nombre . ' ' .
                    $pedido->cliente->usuario->apellido_paterno
                );
            }

            return [
                'id' => $pedido->id,
                'nombre_cliente' => $nombreCliente,
                'direccion_envio' => $pedido->direccionEnvio
                    ? $pedido->direccionEnvio->direccion_completa
                    : 'Recoge en sucursal',
                'fecha_pedido' => $pedido->fecha_pedido,
                'estado_pago' => $pedido->estado_pago,
                'estado_envio' => $pedido->estado_envio,
                'notas' => $pedido->notas,
                'creado_en' => $pedido->creado_en,
                'actualizado_en' => $pedido->actualizado_en,
            ];
        });

        return response()->json($pedidos);
    }

    public function actualizarEstadoEnvio(Request $request, $id)
    {
        $request->validate([
            'estado_envio' => 'required|in:procesando pedido,pedido enviado,pedido entregado'
        ]);

        $pedido = Pedido::findOrFail($id);
        $pedido->estado_envio = $request->estado_envio;
        $pedido->save();

        return response()->json(['message' => 'Estado de envío actualizado', 'pedido' => $pedido]);
    }
}