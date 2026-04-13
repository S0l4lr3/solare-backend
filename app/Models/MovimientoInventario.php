<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';
    
    // Desactivamos los timestamps de Laravel porque usamos fecha_movimiento con CURRENT_TIMESTAMP
    public $timestamps = false;

    protected $fillable = [
        'variante_id',
        'tipo', // 'entrada', 'salida', 'ajuste'
        'cantidad',
        'cantidad_anterior',
        'cantidad_nueva',
        'proveedor_id',
        'pedido_id',
        'usuario_id',
        'motivo',
        'fecha_movimiento'
    ];

    /**
     * Relación con la variante de producto
     */
    public function variante()
    {
        return $this->belongsTo(VariantesProducto::class, 'variante_id');
    }

    /**
     * Relación con el usuario que hizo el movimiento
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Relación con el pedido (si aplica)
     */
    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }
}
