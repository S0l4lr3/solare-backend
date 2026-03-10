<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';
    const CREATED_AT = 'fecha_movimiento';
    const UPDATED_AT = null;

    protected $fillable = [
        'variante_id',
        'tipo',
        'cantidad',
        'cantidad_anterior',
        'cantidad_nueva',
        'proveedor_id',
        'pedido_id',
        'usuario_id',
        'motivo'
    ];

    public function variante()
    {
        return $this->belongsTo(VarianteProducto::class, 'variante_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
