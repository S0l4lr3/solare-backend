<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\DetallePedido;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Calculamos la fecha y hora exacta de hace 3 meses
        $fechaInicio = Carbon::now()->subMonths(3);

        // Pedidos entregados de hace 3 meses para acá
        $pedidosEntregados = Pedido::where('estado_envio', 'entregado')
            ->where('creado_en', '>=', $fechaInicio)
            ->get();

        $cantidadEntregados = $pedidosEntregados->count();

        // Total en dinero de pedidos entregados este mes
        $idsEntregados = $pedidosEntregados->pluck('id');
        $totalDinero = DetallePedido::whereIn('pedido_id', $idsEntregados)
            ->selectRaw('SUM(precio_unitario * cantidad) as total')
            ->value('total') ?? 0;

        // Piezas en stock (conteo de productos)
        $piezasStock = Producto::sum('stock');

        // Pedidos activos (no entregados)
        $pedidosActivos = Pedido::where('estado_envio', '!=', 'entregado')->count();

        // Últimos 5 detalles de pedido
        $pedidosRecientes = DetallePedido::with([
            'pedido.cliente.usuario',
            'variante.producto'
        ])
            ->latest('creado_en')
            ->take(5)
            ->get()
            ->map(function ($detalle) {
                return [
                    'pedido_id' => $detalle->pedido_id,
                    'cliente' => $detalle->pedido->cliente->usuario
                        ? trim($detalle->pedido->cliente->usuario->nombre . ' ' . $detalle->pedido->cliente->usuario->apellido_paterno)
                        : 'Sin nombre',
                    'producto' => $detalle->variante->producto->nombre ?? 'Sin producto',
                    'cantidad' => $detalle->cantidad,
                    'total' => '$' . number_format($detalle->precio_unitario * $detalle->cantidad, 2),
                    'estado_envio' => $detalle->pedido->estado_envio ?? '—',
                ];
            });

        $masVendidos = DB::table('detalles_pedido')
            ->join('pedidos', 'detalles_pedido.pedido_id', '=', 'pedidos.id')
            ->join('variantes_producto', 'detalles_pedido.variante_id', '=', 'variantes_producto.id')
            ->join('productos', 'variantes_producto.producto_id', '=', 'productos.id')
            ->where('pedidos.estado_envio', 'entregado')
            ->select(
                'productos.nombre',
                DB::raw('SUM(detalles_pedido.cantidad) as total_vendido'),
                DB::raw('MAX(detalles_pedido.precio_unitario) as precio_referencia')
            )
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('total_vendido')
            ->limit(3) // Sacamos el Top 3
            ->get();

        return response()->json([
            'ventas_mes' => [
                'cantidad' => $cantidadEntregados,
                'total' => '$' . number_format($totalDinero, 2)
            ],
            'piezas_stock' => $piezasStock,
            'pedidos_activos' => $pedidosActivos,
            'pedidos_recientes' => $pedidosRecientes,
            'mas_vendidos' => $masVendidos,
        ]);
    }
}