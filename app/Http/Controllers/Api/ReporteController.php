<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\DetallePedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteController extends Controller
{
    /**
     * Resumen de ventas para gráficas y dashboard (JSON).
     */
    public function ventasResumen()
    {
        // 1. Ventas totales por mes (Últimos 6 meses)
        $ventasMensuales = DetallePedido::select(
                DB::raw('MONTH(creado_en) as mes'),
                DB::raw('SUM(cantidad * precio_unitario) as total')
            )
            ->groupBy('mes')
            ->orderBy('mes', 'desc')
            ->take(6)
            ->get();

        // 2. Top 5 productos más vendidos
        $topProductos = DetallePedido::with('variante.producto')
            ->select('variante_id', DB::raw('SUM(cantidad) as total_vendido'))
            ->groupBy('variante_id')
            ->orderBy('total_vendido', 'desc')
            ->take(5)
            ->get();

        // 3. Estadísticas generales
        $totalVentas = DetallePedido::sum(DB::raw('cantidad * precio_unitario'));
        $totalPedidos = Pedido::count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'resumen' => [
                    'total_ingresos' => $totalVentas,
                    'total_pedidos' => $totalPedidos,
                    'ticket_promedio' => $totalPedidos > 0 ? $totalVentas / $totalPedidos : 0
                ],
                'grafica_ventas' => $ventasMensuales,
                'top_productos' => $topProductos
            ]
        ]);
    }

    /**
     * Generar reporte en PDF.
     */
    public function exportPdf()
    {
        $data = [
            'titulo' => 'Reporte Ejecutivo de Ventas - SOLARE',
            'fecha' => now()->format('d/m/Y'),
            'pedidos' => Pedido::with(['cliente.usuario', 'detalles.variante.producto'])->orderBy('creado_en', 'desc')->get(),
            'total' => DetallePedido::sum(DB::raw('cantidad * precio_unitario'))
        ];

        // Nota: Necesitarás crear la vista resources/views/reportes/ventas_pdf.blade.php
        $pdf = Pdf::loadView('reportes.ventas_pdf', $data);
        
        return $pdf->download('Reporte_Ventas_Solare.pdf');
    }
}
