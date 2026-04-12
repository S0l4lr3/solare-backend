<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inventario Solare - Reporte Ejecutivo</title>
    <style>
        /* Configuración de página horizontal */
        @page { margin: 2cm; }
        
        body { 
            font-family: 'Helvetica', sans-serif; 
            color: #2d3748; 
            margin: 0; 
            line-height: 1.2; 
            font-size: 10px;
        }
        
        .header-table { width: 100%; border-bottom: 2px solid #958174; margin-bottom: 20px; padding-bottom: 10px; }
        .brand { font-size: 28px; letter-spacing: 8px; color: #958174; font-weight: bold; margin: 0; }
        .subtitle { font-size: 9px; text-transform: uppercase; letter-spacing: 3px; color: #718096; }
        
        .info-section { margin-bottom: 20px; color: #4a5568; }
        
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th { 
            background-color: #f7fafc; 
            color: #958174; 
            font-size: 8px; 
            text-transform: uppercase; 
            padding: 10px 5px; 
            border-bottom: 1px solid #edf2f7; 
            text-align: left;
        }
        td { 
            padding: 8px 5px; 
            border-bottom: 1px solid #edf2f7; 
            word-wrap: break-word; 
            vertical-align: middle;
        }
        
        .sku { font-family: 'Courier', monospace; font-weight: bold; color: #958174; font-size: 9px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .total-box { 
            margin-top: 20px; 
            float: right; 
            width: 300px; 
            background: #958174; 
            color: white; 
            padding: 15px; 
            text-align: right;
        }
        .total-label { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .total-amount { font-size: 20px; font-weight: bold; }
        
        .footer { 
            position: fixed; 
            bottom: -1cm; 
            left: 0; 
            right: 0; 
            height: 1cm; 
            text-align: center; 
            font-size: 8px; 
            color: #a0aec0; 
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                <p class="brand">SOLARE</p>
                <p class="subtitle">Muebles de Exterior</p>
            </td>
            <td class="text-right">
                <p style="font-size: 14px; font-weight: bold; margin: 0; color: #2d3748;">INVENTARIO DE ALMACÉN</p>
                <p style="margin: 0; color: #718096;">REPORTE DE EXISTENCIAS Y VALUACIÓN</p>
            </td>
        </tr>
    </table>

    <div class="info-section">
        Generado el: <strong>{{ $fecha }}</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th width="12%">SKU</th>
                <th width="28%">PRODUCTO</th>
                <th width="15%">CATEGORÍA</th>
                <th width="15%">MATERIAL / ACABADO</th>
                <th width="10%" class="text-center">STOCK</th>
                <th width="10%" class="text-right">P. UNITARIO</th>
                <th width="10%" class="text-right">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($inventario as $item)
                @php 
                    $precio = ($item->producto->precio_base ?? 0) + ($item->precio_adicional ?? 0);
                    $subtotal = $precio * $item->existencias;
                @endphp
                <tr>
                    <td class="sku">{{ $item->sku_especifico }}</td>
                    <td><strong style="color: #2d3748;">{{ $item->producto->nombre }}</strong></td>
                    <td>{{ $item->producto->categoria->nombre ?? 'N/A' }}</td>
                    <td>{{ $item->material->nombre ?? 'Acabado Base' }}</td>
                    <td class="text-center">
                        <span style="{{ $item->existencias < 3 ? 'color: #e53e3e; font-weight: bold;' : '' }}">
                            {{ $item->existencias }}
                        </span>
                    </td>
                    <td class="text-right">${{ number_format($precio, 2) }}</td>
                    <td class="text-right"><strong>${{ number_format($subtotal, 2) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-box">
        <div class="total-label">VALUACIÓN TOTAL DEL INVENTARIO CONSULTADO</div>
        <div class="total-amount">${{ number_format($totalValuacion, 2) }} <span style="font-size: 10px;">MXN</span></div>
    </div>

    <div class="footer">
        Solare Muebles Administrativo - Confidencial - Página generada el {{ date('d/m/Y H:i') }}
    </div>
</body>
</html>
