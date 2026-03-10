<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallePedido extends Model
{
    protected $table = 'detalles_pedido';
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = null; // No tiene columna de actualización

    protected $fillable = [
        'pedido_id',
        'variante_id',
        'cantidad',
        'precio_unitario'
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function variante()
    {
        return $this->belongsTo(VarianteProducto::class, 'variante_id');
    }
}
