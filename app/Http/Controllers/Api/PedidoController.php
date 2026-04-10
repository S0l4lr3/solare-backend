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

    public function update(Request $request, $id)
    {
       
        $pedido = Pedido::findOrFail($id);

        // Si solo viene estado_envio, solo actualizamos eso
        if ($request->has('estado_envio')) {
            $request->validate([
                'estado_envio' => 'required|in:procesando,en_camino,entregado,cancelado'
            ]);
            $pedido->estado_envio = $request->estado_envio;
            $pedido->save();
            return response()->json(['message' => 'Estado de envío actualizado', 'pedido' => $pedido]);
        }

        // Si vienen más campos, actualización general
        $pedido->fill($request->only([
            'direccion_envio_id',
            'fecha_pedido',
            'estado_pago',
            'estado_envio',
            'notas'
        ]));
        $pedido->save();

        return response()->json(['message' => 'Pedido actualizado', 'pedido' => $pedido]);
    }
}