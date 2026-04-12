<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallePedido extends Model
{
    protected $table = 'detalles_pedido';
    const CREATED_AT = 'creado_en';
    public $timestamps = false;

    protected $fillable = [
        'pedido_id', 'variante_id', 'cantidad', 'precio_unitario'
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function variante()
    {
        return $this->belongsTo(VariantesProducto::class, 'variante_id');
    }
}