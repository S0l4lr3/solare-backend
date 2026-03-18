<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';

    // Definición obligatoria de los nombres de columnas de tiempo reales
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'categoria_id', 'nombre', 'descripcion', 'imagen_url', 'precio_base', 'stock', 'sku_base', 'activo'
    ];

    public function imagenes()
    {
        return $this->hasMany(ImagenProducto::class, 'producto_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}