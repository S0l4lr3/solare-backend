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

    protected $appends = ['full_image_url'];

    public function getFullImageUrlAttribute()
    {
        if (!$this->imagen_url) {
            return null;
        }

        // Si ya es una URL completa (empezando con http), la devolvemos tal cual
        if (str_starts_with($this->imagen_url, 'http')) {
            return $this->imagen_url;
        }

        // Buscamos una URL base para imágenes en el .env, si no, usamos la local
        $basePath = env('IMAGE_URL', asset('storage/'));
        
        // Aseguramos que el basePath termine en /
        $basePath = rtrim($basePath, '/') . '/';

        return $basePath . ltrim($this->imagen_url, '/');
    }

    public function imagenes()
    {
        return $this->hasMany(ImagenProducto::class, 'producto_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}