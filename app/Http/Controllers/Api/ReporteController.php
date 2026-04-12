<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\InventarioExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\VariantesProducto;
use Illuminate\Support\Carbon;

class ReporteController extends Controller
{
    /**
     * Exportación a Excel (.xlsx) via puente
     */
    public function exportarCSV(Request $request)
    {
        $filtros = $request->only(['search', 'categoria_id', 'material_id']);
        $nombreArchivo = 'Inventario_Solare_' . Carbon::now()->format('d_m_Y') . '.xlsx';

        return Excel::download(new InventarioExport($filtros), $nombreArchivo);
    }

    /**
     * Exportación a PDF (MODO HORIZONTAL - DISEÑO EJECUTIVO)
     */
    public function exportPdf(Request $request)
    {
        $query = VariantesProducto::with(['producto.categoria', 'material']);
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('producto', function($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('categoria_id')) {
            $catId = $request->categoria_id;
            $query->whereHas('producto', function($q) use ($catId) {
                $q->where('categoria_id', $catId);
            });
        }

        $inventario = $query->get();
        $totalValuacion = $inventario->sum(function($i) {
            return (($i->producto->precio_base ?? 0) + ($i->precio_adicional ?? 0)) * $i->existencias;
        });

        // Generamos el PDF forzando el formato LANDSCAPE (Horizontal)
        $pdf = Pdf::loadView('reportes.inventario_pdf', [
            'inventario' => $inventario,
            'fecha' => Carbon::now()->format('d/m/Y H:i'),
            'totalValuacion' => $totalValuacion
        ])->setPaper('a4', 'landscape'); // <--- AQUÍ ESTÁ EL CAMBIO MAESTRO

        return $pdf->download('Reporte_Inventario_Solare.pdf');
    }

    public function ventasResumen()
    {
        return response()->json(['status' => 'success', 'message' => 'Resumen de ventas activo.']);
    }
}
