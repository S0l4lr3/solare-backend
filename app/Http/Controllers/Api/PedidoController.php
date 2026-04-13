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

                $cliente = Cliente::where('usuario_id', $usuario->id)->first();
                if (!$cliente) {
                    $cliente = Cliente::create([
                        'usuario_id' => $usuario->id,
                        'telefono' => $request->telefono ?? '0000000000',
                    ]);
                }

                $pedido = Pedido::create([
                    'cliente_id' => $cliente->id,
                    'direccion_envio_id' => $request->direccion_envio_id ?? null,
                    'fecha_pedido' => now(),
                    'total' => $request->total,
                    'estado_pago' => 'pendiente',
                    'estado_envio' => 'procesando',
                    'notas' => $request->notas ?? 'Pedido realizado desde la web de Solare'
                ]);

                foreach ($request->items as $item) {
                    $varianteId = $item['id'];
                    
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
            return response()->json(['status' => 'error', 'mensaje' => $e->getMessage()], 500);
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

    /**
     * Cancelar un pedido (Cliente)
     */
    public function cancelar(Request $request, $id)
    {
        try {
            return DB::transaction(function () use ($request, $id) {
                $usuario = $request->user();
                $cliente = Cliente::where('usuario_id', $usuario->id)->first();

                if (!$cliente) {
                    return response()->json(['status' => 'error', 'mensaje' => 'No se encontró perfil de cliente.'], 404);
                }

                $pedido = Pedido::with('detalles.variante')->where('id', $id)->where('cliente_id', $cliente->id)->firstOrFail();

                if ($pedido->estado_envio !== 'procesando') {
                    return response()->json(['status' => 'error', 'mensaje' => 'El pedido no puede cancelarse en su estado actual.'], 400);
                }

                if ($pedido->estado_pago === 'pagado') {
                    foreach ($pedido->detalles as $detalle) {
                        $variante = $detalle->variante;
                        if ($variante) {
                            $cantidadAnterior = $variante->existencias;
                            $nuevaCantidad = $cantidadAnterior + $detalle->cantidad;
                            $variante->update(['existencias' => $nuevaCantidad]);

                            \App\Models\MovimientoInventario::create([
                                'variante_id' => $variante->id,
                                'tipo' => 'entrada',
                                'cantidad' => $detalle->cantidad,
                                'cantidad_anterior' => $cantidadAnterior,
                                'cantidad_nueva' => $nuevaCantidad,
                                'pedido_id' => $pedido->id,
                                'usuario_id' => $usuario->id,
                                'motivo' => 'Devolución de stock por cancelación de pedido #' . $pedido->id
                            ]);
                        }
                    }
                }

                $pedido->update(['estado_envio' => 'cancelado']);

                return response()->json(['status' => 'success', 'mensaje' => 'Pedido cancelado correctamente.']);
            });
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'mensaje' => $e->getMessage()], 500);
        }
    }

    /**
     * Listar todos los pedidos (Administración)
     */
    public function index(Request $request)
    {
        $query = Pedido::with('cliente.usuario');

        // Filtro por Cliente (Búsqueda por nombre)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('cliente.usuario', function($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%");
            });
        }

        // Filtro por Estado de Envío
        if ($request->filled('estado_envio')) {
            $query->where('estado_envio', $request->estado_envio);
        }

        // Filtro por Estado de Pago
        if ($request->filled('estado_pago')) {
            $query->where('estado_pago', $request->estado_pago);
        }

        // Filtro por Rango de Fechas
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_pedido', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_pedido', '<=', $request->fecha_fin);
        }

        $pedidos = $query->latest('fecha_pedido')->get();

        return response()->json($pedidos);
    }

    /**
     * Mostrar detalle de un pedido (Administración - Empaquetado)
     */
    public function show($id)
    {
        $pedido = Pedido::with([
            'cliente.usuario', 
            'detalles.variante.producto', 
            'detalles.variante.material',
            'direccionEnvio'
        ])->findOrFail($id);

        return response()->json($pedido);
    }

    /**
     * Actualizar estado de un pedido (Administración)
     */
    public function update(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);
        
        $request->validate([
            'estado_envio' => 'nullable|string',
            'estado_pago' => 'nullable|string',
            'notas' => 'nullable|string'
        ]);

        $pedido->update($request->only(['estado_envio', 'estado_pago', 'notas']));

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Pedido actualizado correctamente.',
            'pedido' => $pedido
        ]);
    }
}
