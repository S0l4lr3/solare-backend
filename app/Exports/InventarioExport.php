<?php

namespace App\Exports;

use App\Models\VariantesProducto;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;

class InventarioExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithColumnFormatting, WithCustomStartCell
{
    protected $filtros;

    public function __construct($filtros)
    {
        $this->filtros = $filtros;
    }

    public function startCell(): string
    {
        return 'A2'; // Dejamos la fila 1 para el título
    }

    public function query()
    {
        $query = VariantesProducto::with(['producto.categoria', 'material']);

        if (isset($this->filtros['search'])) {
            $search = $this->filtros['search'];
            $query->whereHas('producto', function($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%");
            });
        }

        if (isset($this->filtros['categoria_id'])) {
            $catId = $this->filtros['categoria_id'];
            $query->whereHas('producto', function($q) use ($catId) {
                $q->where('categoria_id', $catId);
            });
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'SKU ÚNICO',
            'NOMBRE DEL PRODUCTO',
            'CATEGORÍA',
            'MATERIAL / ACABADO',
            'EXISTENCIAS',
            'PRECIO UNITARIO',
            'VALOR EN BODEGA'
        ];
    }

    public function map($variante): array
    {
        $precio = ($variante->producto->precio_base ?? 0) + ($variante->precio_adicional ?? 0);
        
        return [
            $variante->sku_especifico ?? 'N/A',
            strtoupper($variante->producto->nombre ?? 'Sin nombre'),
            strtoupper($variante->producto->categoria->nombre ?? 'General'),
            strtoupper($variante->material->nombre ?? $variante->color ?? 'Base'),
            $variante->existencias,
            $precio,
            $precio * $variante->existencias
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
            'G' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Título del reporte en la celda A1
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'SOLARE MUEBLES - REPORTE DE INVENTARIO CENTRAL');
        
        return [
            // Estilo del título principal
            1    => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '958174']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            // Estilo de los encabezados de tabla
            2    => [
                'font' => ['bold' => true, 'color' => ['rgb' => '958174']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F9F9F9']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            // Alineación de datos
            'A'  => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],
            'E'  => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'F:G' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
        ];
    }
}
