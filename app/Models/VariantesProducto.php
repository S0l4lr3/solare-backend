<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantesProducto extends Model
{
    protected $table = 'variantes_producto';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'producto_id',
        'material_id',
        'sku_especifico',
        'color',
        'precio_adicional',
        'existencias',
        'activo'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function material()
    {
        return $this->belongsTo(materiales::class, 'material_id');
    }
}
