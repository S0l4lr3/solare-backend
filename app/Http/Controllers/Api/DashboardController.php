<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\DetallePedido;
use App\Models\VariantesProducto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Rango de tiempo (últimos 3 meses)
        $fechaInicio = Carbon::now()->subMonths(3);

        // 2. Ventas (Usando 'fecha_pedido' según el esquema SQL real)
        $pedidosVentas = Pedido::where('fecha_pedido', '>=', $fechaInicio)->get();
        $cantidadVentas = $pedidosVentas->count();

        $idsPedidos = $pedidosVentas->pluck('id');
        $totalDinero = DetallePedido::whereIn('pedido_id', $idsPedidos)
            ->selectRaw('SUM(precio_unitario * cantidad) as total')
            ->value('total') ?? 0;

        // 3. Stock Real
        $piezasStock = VariantesProducto::sum('existencias');

        // 4. Valor del Inventario
        $valorInventario = DB::table('variantes_producto')
            ->join('productos', 'variantes_producto.producto_id', '=', 'productos.id')
            ->selectRaw('SUM((productos.precio_base + variantes_producto.precio_adicional) * variantes_producto.existencias) as total')
            ->value('total') ?? 0;

        // 5. Productos con Stock Crítico
        $stockCritico = VariantesProducto::with('producto')
            ->where('existencias', '<', 3)
            ->get()
            ->map(function($v) {
                return [
                    'nombre' => ($v->producto->nombre ?? 'Mueble') . ' (' . ($v->color ?? 'Base') . ')',
                    'stock' => $v->existencias
                ];
            });

        // 6. Pedidos Activos
        $pedidosActivos = Pedido::whereNotIn('estado_envio', ['entregado', 'cancelado'])->count();

        // 7. Pedidos Recientes (Usando 'creado_en' que sí existe en detalles_pedido)
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
                        : 'Cliente Solare',
                    'producto' => $detalle->variante->producto->nombre ?? 'Mueble',
                    'cantidad' => $detalle->cantidad,
                    'total' => '$' . number_format($detalle->precio_unitario * $detalle->cantidad, 2),
                    'estado_envio' => $detalle->pedido->estado_envio ?? 'Pendiente',
                ];
            });

        // 8. Top 3 Más Vendidos
        $masVendidos = DB::table('detalles_pedido')
            ->join('variantes_producto', 'detalles_pedido.variante_id', '=', 'variantes_producto.id')
            ->join('productos', 'variantes_producto.producto_id', '=', 'productos.id')
            ->select(
                'productos.nombre',
                DB::raw('SUM(detalles_pedido.cantidad) as total_vendido'),
                DB::raw('MAX(detalles_pedido.precio_unitario) as precio_referencia')
            )
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('total_vendido')
            ->limit(3)
            ->get();

        return response()->json([
            'status' => 'success',
            'ventas_mes' => [
                'cantidad' => $cantidadVentas,
                'total' => '$' . number_format($totalDinero, 2)
            ],
            'piezas_stock' => $piezasStock,
            'valor_inventario' => '$' . number_format($valorInventario, 2),
            'pedidos_activos' => $pedidosActivos,
            'pedidos_recientes' => $pedidosRecientes,
            'mas_vendidos' => $masVendidos,
            'stock_critico' => $stockCritico,
            'ajustes_manuales_24h' => DB::table('movimientos_inventario')
                ->where('fecha_movimiento', '>=', Carbon::now()->subDay()) // Corregido: 'fecha_movimiento'
                ->where('tipo', 'ajuste')
                ->count()
        ]);
    }
}
