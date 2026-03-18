<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class materiales extends Model
{
    protected $table = 'materiales';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombre',
        'descripcion'
    ];

    public function variantesProducto()
    {
        return $this->hasMany(VariantesProducto::class, 'material_id');
    }
}
