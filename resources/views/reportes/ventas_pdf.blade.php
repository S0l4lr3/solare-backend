<!DOCTYPE html>
<html>
<head>
    <title>{{ $titulo }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #b8860b; padding-bottom: 10px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; }
        .summary { margin: 20px 0; background: #f9f9f9; padding: 15px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background-color: #b8860b; color: white; padding: 10px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #ddd; font-size: 12px; }
        .total-box { text-align: right; margin-top: 30px; font-size: 18px; font-weight: bold; color: #b8860b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $titulo }}</h1>
        <p>Fecha de generación: {{ $fecha }}</p>
    </div>

    <div class="summary">
        <h3>Resumen Ejecutivo</h3>
        <p>Total de Ingresos: <strong>${{ number_format($total, 2) }}</strong></p>
        <p>Pedidos Registrados: <strong>{{ $pedidos->count() }}</strong></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID Pedido</th>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pedidos as $pedido)
                @php 
                    $montoPedido = $pedido->detalles->sum(function($d) { return $d->cantidad * $d->precio_unitario; });
                @endphp
                <tr>
                    <td>#{{ $pedido->id }}</td>
                    <td>{{ $pedido->cliente->usuario->nombre }} {{ $pedido->cliente->usuario->apellido_paterno }}</td>
                    <td>{{ $pedido->creado_en->format('d/m/Y') }}</td>
                    <td>{{ strtoupper($pedido->estado_pago) }}</td>
                    <td>${{ number_format($montoPedido, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-box">
        TOTAL GENERAL: ${{ number_format($total, 2) }}
    </div>

    <div class="footer">
        SOLARE - Mueblería de Lujo &copy; {{ date('Y') }}
    </div>
</body>
</html>
