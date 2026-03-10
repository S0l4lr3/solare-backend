<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VarianteProducto extends Model
{
    protected $table = 'variantes_producto';

    protected $fillable = [
        'producto_id',
        'material_id',
        'color',
        'precio_adicional',
        'existencias',
        'sku_especifico',
        'activo'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function historialPrecios()
    {
        return $this->hasMany(HistorialPrecio::class, 'variante_id');
    }
}
