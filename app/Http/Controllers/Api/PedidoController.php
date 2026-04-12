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
        $pedido = Pedido::with('detalles')->findOrFail($id);
        $estadoAnterior = $pedido->estado_envio;

        // Si solo viene estado_envio, solo actualizamos eso
        if ($request->has('estado_envio')) {
            $request->validate([
                'estado_envio' => 'required|in:procesando,en_camino,entregado,cancelado'
            ]);

            try {
                return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $pedido, $estadoAnterior) {
                    
                    // Lógica de Unificación: Si el pedido cambia a ENTREGADO, descontamos stock
                    if ($request->estado_envio === 'entregado' && $estadoAnterior !== 'entregado') {
                        foreach ($pedido->detalles as $detalle) {
                            if ($detalle->variante_id) {
                                $variante = \App\Models\VariantesProducto::lockForUpdate()->findOrFail($detalle->variante_id);
                                
                                if ($variante->existencias < $detalle->cantidad) {
                                    throw new \Exception("Stock insuficiente para la variante ID: {$detalle->variante_id}.");
                                }

                                $cantidadAnterior = $variante->existencias;
                                $variante->existencias -= $detalle->cantidad;
                                $variante->save();

                                // Registro del Movimiento (Kardex) para auditoría contra robo hormiga
                                \App\Models\MovimientoInventario::create([
                                    'variante_id' => $variante->id,
                                    'tipo' => 'salida',
                                    'cantidad' => $detalle->cantidad,
                                    'cantidad_anterior' => $cantidadAnterior,
                                    'cantidad_nueva' => $variante->existencias,
                                    'usuario_id' => $request->user() ? $request->user()->id : 1, // Usuario 1 por defecto si es vía API/Job
                                    'motivo' => "Venta - Pedido #{$pedido->id} (Entregado)"
                                ]);
                            }
                        }
                    }

                    $pedido->estado_envio = $request->estado_envio;
                    $pedido->save();

                    return response()->json([
                        'message' => 'Estado de envío y stock actualizados correctamente',
                        'pedido' => $pedido->load('detalles')
                    ]);
                });
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error en la unificación de inventario: ' . $e->getMessage()
                ], 400);
            }
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