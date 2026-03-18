<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedido';
     const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'cliente_id',
        'direccion_envio_id',
        'fecha_pedido',
        'estado_pago',
        'estado_envio',
        'notas',
        'creado_en',
        'actualizado_en'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function direccionEnvio()
    {
        return $this->belongsTo(DireccionEnvio::class, 'direccion_envio_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class, 'pedido_id');
    }


}
