<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $table = 'materiales';
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = ['nombre', 'descripcion'];

    public function variantes()
    {
        return $this->hasMany(VarianteProducto::class, 'material_id');
    }
}
