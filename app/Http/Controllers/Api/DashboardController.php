<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\DetallePedido;
use App\Models\VariantesProducto;
use App\Models\MovimientoInventario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            // 1. Rango de tiempo (Mes actual)
            $inicioMes = Carbon::now()->startOfMonth();

            // 2. Ventas Reales (Solo lo PAGADO)
            $ventasMes = Pedido::where('estado_pago', 'pagado')
                ->where('fecha_pedido', '>=', $inicioMes)
                ->selectRaw('COUNT(*) as cantidad, SUM(total) as monto')
                ->first();

            // 3. Stock Real
            $piezasStock = VariantesProducto::sum('existencias') ?? 0;

            // 4. Valor del Inventario
            $valorInventario = DB::table('variantes_producto')
                ->join('productos', 'variantes_producto.producto_id', '=', 'productos.id')
                ->selectRaw('SUM((productos.precio_base + variantes_producto.precio_adicional) * variantes_producto.existencias) as total')
                ->value('total') ?? 0;

            // 5. Alerta de Stock Crítico
            $stockCritico = VariantesProducto::with('producto')
                ->where('existencias', '<', 3)
                ->take(10)
                ->get()
                ->map(function($v) {
                    return [
                        'nombre' => ($v->producto->nombre ?? 'Mueble') . ' (' . ($v->color ?? 'Base') . ')',
                        'stock' => $v->existencias
                    ];
                });

            // 6. Pedidos en Operación
            $pedidosActivos = Pedido::whereIn('estado_envio', ['procesando', 'en_camino'])->count();

            // 7. Auditoría Anti-Robo (Ajustes manuales)
            $ajustes24h = MovimientoInventario::where('tipo', 'ajuste')
                ->where('fecha_movimiento', '>=', Carbon::now()->subDay())
                ->count();

            // 8. Pedidos Recientes (Protección contra NULOS)
            $pedidosRecientes = Pedido::with(['cliente.usuario', 'detalles.variante.producto'])
                ->latest('fecha_pedido')
                ->take(5)
                ->get()
                ->map(function ($p) {
                    $primerDetalle = $p->detalles->first();
                    return [
                        'pedido_id' => $p->id,
                        'cliente' => $p->cliente->usuario->nombre ?? 'Cliente Solare',
                        'producto' => $primerDetalle->variante->producto->nombre ?? 'Mueble Solare',
                        'cantidad' => $primerDetalle->cantidad ?? 1,
                        'total' => '$' . number_format($p->total, 2),
                        'estado_envio' => $p->estado_envio,
                    ];
                });

            // 9. Top 3 Más Vendidos
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
                    'cantidad' => $ventasMes->cantidad ?? 0,
                    'total' => '$' . number_format($ventasMes->monto ?? 0, 2)
                ],
                'piezas_stock' => $piezasStock,
                'valor_inventario' => '$' . number_format($valorInventario, 2),
                'pedidos_activos' => $pedidosActivos,
                'pedidos_recientes' => $pedidosRecientes,
                'mas_vendidos' => $masVendidos,
                'stock_critico' => $stockCritico,
                'ajustes_manuales_24h' => $ajustes24h
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno en Dashboard: ' . $e->getMessage()
            ], 500);
        }
    }
}
