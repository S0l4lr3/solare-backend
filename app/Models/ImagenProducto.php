<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImagenProducto extends Model
{
    protected $table = 'imagenes_producto';
    public $timestamps = false;

    protected $fillable = [
        'producto_id', 'url', 'es_principal', 'orden'
    ];

    protected $appends = ['full_image_url', 'full_url'];

    /**
     * Alias de full_image_url para compatibilidad con el frontend
     */
    public function getFullUrlAttribute()
    {
        return $this->full_image_url;
    }

    /**
     * Obtener la URL completa de la imagen.
     */
    public function getFullImageUrlAttribute()
    {
        if (!$this->url) {
            return null;
        }

        if (str_starts_with($this->url, 'http')) {
            return $this->url;
        }

        $basePath = env('IMAGE_URL', asset('storage/'));
        $basePath = rtrim($basePath, '/') . '/';

        return $basePath . ltrim($this->url, '/');
    }

    public function producto()    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
